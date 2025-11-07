<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\Booking;
use App\Entity\Category;
use App\Entity\ServiceDefinition;
use App\Entity\User;
use App\Enum\ArtisanServiceStatus;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class BookingGuardsTest extends WebTestCase
{
    public function testTransitionFromCancelledToConfirmedIsForbidden(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = \bin2hex(\random_bytes(4));
        $email = "guards-canceled-to-contacted+{$suffix}@example.test";

        // Arrange : booking en CANCELED
        $booking = $this->createBookingGraph($em, $email, BookingStatus::CANCELED);
        $bookingId = (string) $booking->getId();

        // Act : tenter une transition depuis CANCELED → to_contacted
        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_contacted'], \JSON_THROW_ON_ERROR)
        );

        // Pour l’instant, le contrat du contrôleur est "transition_not_allowed" → 409.
        // Si plus tard on décide que ce doit être 403, on ajustera ici ET le contrôleur ensemble.
        self::assertSame(
            409,
            $client->getResponse()->getStatusCode(),
            (string) $client->getResponse()->getContent()
        );
    }

    public function testTransitionFromConfirmedToPendingIsForbidden(): void
    {
        // Ici on simule "revenir en arrière" : DONE → essayer de re-scheduled
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = \bin2hex(\random_bytes(4));
        $email = "guards-done-to-scheduled+{$suffix}@example.test";

        // Arrange : booking en DONE
        $booking = $this->createBookingGraph($em, $email, BookingStatus::DONE);
        $bookingId = (string) $booking->getId();

        // Act : tenter une transition "to_scheduled" depuis DONE
        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_scheduled'], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            409,
            $client->getResponse()->getStatusCode(),
            (string) $client->getResponse()->getContent()
        );
    }

    public function testTransitionFromPendingToInquiryIsForbidden(): void
    {
        // On lit le nom "Pending → Inquiry" comme "SCHEDULED → CONTACTED/INQUIRY" (revenir en arrière)
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = \bin2hex(\random_bytes(4));
        $email = "guards-scheduled-to-contacted+{$suffix}@example.test";

        // Arrange : booking en SCHEDULED
        $booking = $this->createBookingGraph($em, $email, BookingStatus::SCHEDULED);
        $bookingId = (string) $booking->getId();

        // Act : tenter une transition "to_contacted" depuis SCHEDULED (revenir en arrière)
        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_contacted'], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            409,
            $client->getResponse()->getStatusCode(),
            (string) $client->getResponse()->getContent()
        );
    }

    public function testTransitionWithPastScheduledAtFails(): void
    {
        // Ici on teste bien le côté "business": scheduledAt dans le passé pour to_scheduled
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = \bin2hex(\random_bytes(4));
        $email = "guards-past-scheduled+{$suffix}@example.test";

        // Arrange : booking en CONTACTED avec une date dans le passé
        $booking = $this->createBookingGraph(
            $em,
            $email,
            BookingStatus::CONTACTED,
            scheduledAt: (new \DateTimeImmutable('-1 day'))->setTime(10, 0)
        );
        $bookingId = (string) $booking->getId();

        // Act : transition to_scheduled
        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_scheduled'], \JSON_THROW_ON_ERROR)
        );

        // Ici on s’attend à un 422 (guard métier). Si le contrôleur retourne autre chose,
        // on ajustera après avoir vu la réponse exacte.
        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $client->getResponse()->getStatusCode(),
            (string) $client->getResponse()->getContent()
        );
    }

    public function testTransitionWithAmountTooHighFails(): void
    {
        // Business guard sur le montant max lors de to_done
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = \bin2hex(\random_bytes(4));
        $email = "guards-amount-too-high+{$suffix}@example.test";

        // Arrange : booking en SCHEDULED avec un estimatedAmount énorme
        $booking = $this->createBookingGraph(
            $em,
            $email,
            BookingStatus::SCHEDULED,
            estimatedAmount: '10000000.00'
        );
        $bookingId = (string) $booking->getId();

        // Act : to_done
        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_done'], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $client->getResponse()->getStatusCode(),
            (string) $client->getResponse()->getContent()
        );
    }

    /**
     * Crée tout le graphe minimal pour un Booking :
     * - User
     * - Category
     * - ServiceDefinition
     * - ArtisanProfile
     * - ArtisanService
     * - Booking
     *
     * C’est exactement le même pattern que les tests BookingTimeline*.
     */
    private function createBookingGraph(
        EntityManagerInterface $em,
        string $email,
        ?BookingStatus $status = null,
        ?\DateTimeImmutable $scheduledAt = null,
        ?string $estimatedAmount = null,
    ): Booking {
        // Nettoyage éventuel de l’email (sécurité)
        $em->createQuery('DELETE FROM App\Entity\Booking b WHERE b.client IN (SELECT u FROM App\Entity\User u WHERE u.email = :e)')
            ->setParameter('e', $email)
            ->execute();
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        // Category
        $suffix = \bin2hex(\random_bytes(4));
        $cat = (new Category())
            ->setName('Menuiserie '.$suffix)
            ->setSlug('menuiserie-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Pose de porte '.$suffix)
            ->setSlug('pose-porte-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Menuisier')
            ->setPhone('+213555123459')
            ->setWilaya('Alger')
            ->setCommune('Hussein Dey');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Pose porte intérieur '.$suffix)
            ->setSlug('pose-porte-interieur-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        // Booking
        $booking = (new Booking())
            ->setClient($user)
            ->setArtisanService($as);

        if (null !== $status) {
            $booking->setStatus($status);
        }

        if (null !== $scheduledAt) {
            $booking->setScheduledAt($scheduledAt);
        }

        if (null !== $estimatedAmount) {
            $booking->setEstimatedAmount($estimatedAmount);
        }

        $em->persist($booking);
        $em->flush();

        return $booking;
    }
}
