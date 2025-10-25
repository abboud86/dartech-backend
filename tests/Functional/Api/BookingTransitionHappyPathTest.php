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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookingTransitionHappyPathTest extends WebTestCase
{
    public function testToContactedFromInquiry(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // Suffixe unique pour éviter toute collision (emails, slugs, titres)
        $suffix = bin2hex(random_bytes(4));
        $email = "hp-contacted+{$suffix}@example.test";

        // --- Arrange: données minimales persistées ---

        // 1) User pour l'auth de test (pas de delete préalable pour éviter les FKs)
        $user = (new User())->setEmail($email)->setPassword('x');
        $em->persist($user);

        // 2) Category requise par ServiceDefinition (slug unique)
        $cat = (new Category())
            ->setName('Plomberie '.$suffix)
            ->setSlug('plomberie-'.$suffix);
        $em->persist($cat);

        // 3) ServiceDefinition (slug unique)
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage express '.$suffix)
            ->setSlug('depannage-express-'.$suffix);
        $em->persist($sd);

        // 4) ArtisanProfile minimal
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Test')
            ->setPhone('+213555123456')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // 5) ArtisanService minimal (slug unique)
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention rapide '.$suffix)
            ->setSlug('intervention-rapide-'.$suffix)
            ->setUnitAmount(10000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        // 6) Booking initial
        $booking = (new Booking())
            ->setClient($user)
            ->setArtisanService($as);
        $em->persist($booking);

        $em->flush();
        $em->clear();

        // --- Act: POST transition to_contacted ---
        $client->request(
            'POST',
            '/api/bookings/'.(string) $booking->getId().'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_contacted'], \JSON_THROW_ON_ERROR)
        );

        // --- Assert ---
        $res = $client->getResponse();
        self::assertSame(200, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('to_contacted', $data['applied'] ?? null);
        self::assertSame('CONTACTED', $data['status'] ?? null);
    }

    public function testToScheduledAfterContacted(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // Suffixe unique (emails, slugs, titres)
        $suffix = bin2hex(random_bytes(4));
        $email = "hp-scheduled+{$suffix}@example.test";

        // --- Arrange: données minimales persistées ---
        $user = (new User())->setEmail($email)->setPassword('x');
        $em->persist($user);

        $cat = (new Category())->setName('Electricité '.$suffix)->setSlug('electricite-'.$suffix);
        $em->persist($cat);

        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Installation prise '.$suffix)
            ->setSlug('installation-prise-'.$suffix);
        $em->persist($sd);

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Elec')
            ->setPhone('+213555123457')
            ->setWilaya('Alger')
            ->setCommune('Hydra');
        $em->persist($ap);

        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Branchement '.$suffix)
            ->setSlug('branchement-'.$suffix)
            ->setUnitAmount(15000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $booking = (new Booking())
            ->setClient($user)
            ->setArtisanService($as);
        $em->persist($booking);
        $em->flush();

        // --- Step 1: to_contacted ---
        $client->request(
            'POST',
            '/api/bookings/'.(string) $booking->getId().'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_contacted'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // --- Step 2: définir une date de rdv FUTUR ---
        $booking = $em->getRepository(Booking::class)->find($booking->getId());
        \assert($booking instanceof Booking);
        $booking->setScheduledAt((new \DateTimeImmutable('+1 day'))->setTime(10, 0));
        $em->flush();
        $em->clear();

        // --- Step 3: to_scheduled ---
        $client->request(
            'POST',
            '/api/bookings/'.(string) $booking->getId().'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_scheduled'], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(200, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('to_scheduled', $data['applied'] ?? null);
        self::assertSame('SCHEDULED', $data['status'] ?? null);
    }

    public function testToDoneAfterScheduled(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // Suffixe unique (emails, slugs, titres)
        $suffix = bin2hex(random_bytes(4));
        $email = "hp-done+{$suffix}@example.test";

        // --- Arrange: données minimales persistées ---
        $user = (new User())->setEmail($email)->setPassword('x');
        $em->persist($user);

        $cat = (new Category())->setName('Peinture '.$suffix)->setSlug('peinture-'.$suffix);
        $em->persist($cat);

        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Peinture intérieure '.$suffix)
            ->setSlug('peinture-interieure-'.$suffix);
        $em->persist($sd);

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Peintre')
            ->setPhone('+213555123458')
            ->setWilaya('Alger')
            ->setCommune('El Biar');
        $em->persist($ap);

        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Prestation peinture '.$suffix)
            ->setSlug('prestation-peinture-'.$suffix)
            ->setUnitAmount(25000)
            ->setCurrency('DZD');
        $em->persist($as);

        $booking = (new Booking())
            ->setClient($user)
            ->setArtisanService($as);
        $em->persist($booking);
        $em->flush();

        // Step 1: to_contacted
        $client->request(
            'POST',
            '/api/bookings/'.(string) $booking->getId().'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_contacted'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // Step 2: set scheduledAt futur puis to_scheduled
        $booking = $em->getRepository(Booking::class)->find($booking->getId());
        \assert($booking instanceof Booking);
        $booking->setScheduledAt((new \DateTimeImmutable('+2 days'))->setTime(9, 30));
        $em->flush();
        $em->clear();

        $client->request(
            'POST',
            '/api/bookings/'.(string) $booking->getId().'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_scheduled'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // Step 3: to_done
        $client->request(
            'POST',
            '/api/bookings/'.(string) $booking->getId().'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_done'], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(200, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('to_done', $data['applied'] ?? null);
        self::assertSame('DONE', $data['status'] ?? null);
    }
}
