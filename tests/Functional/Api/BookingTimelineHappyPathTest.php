<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\Booking;
use App\Entity\BookingTimeline;
use App\Entity\Category;
use App\Entity\ServiceDefinition;
use App\Entity\User;
use App\Enum\ArtisanServiceStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookingTimelineHappyPathTest extends WebTestCase
{
    public function testTimelineIsAppendedOnEachTransition(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // Suffixe unique pour éviter toute collision (emails, slugs, titres)
        $suffix = bin2hex(random_bytes(4));
        $email = "hp-timeline+{$suffix}@example.test";

        // --- Arrange: données minimales persistées ---
        $user = (new User())->setEmail($email)->setPassword('x');
        $em->persist($user);

        $cat = (new Category())
            ->setName('Menuiserie '.$suffix)
            ->setSlug('menuiserie-'.$suffix);
        $em->persist($cat);

        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Pose de porte '.$suffix)
            ->setSlug('pose-porte-'.$suffix);
        $em->persist($sd);

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Menuisier')
            ->setPhone('+213555123459')
            ->setWilaya('Alger')
            ->setCommune('Hussein Dey');
        $em->persist($ap);

        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Pose porte intérieur '.$suffix)
            ->setSlug('pose-porte-interieur-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $booking = (new Booking())
            ->setClient($user)
            ->setArtisanService($as);
        $em->persist($booking);
        $em->flush();

        $bookingId = $booking->getId();

        // --- Act 1: to_contacted ---
        $client->request(
            'POST',
            '/api/bookings/'.(string) $bookingId.'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_contacted'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // --- Prépare la date future puis Act 2: to_scheduled ---
        $booking = $em->getRepository(Booking::class)->find($bookingId);
        \assert($booking instanceof Booking);
        $booking->setScheduledAt((new \DateTimeImmutable('+1 day'))->setTime(14, 0));
        $em->flush();

        $client->request(
            'POST',
            '/api/bookings/'.(string) $bookingId.'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_scheduled'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // --- Act 3: to_done ---
        $client->request(
            'POST',
            '/api/bookings/'.(string) $bookingId.'/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_done'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // --- Assert timeline: 3 entrées, ordre chronologique ---
        /** @var BookingTimeline[] $timelines */
        $timelines = $em->getRepository(BookingTimeline::class)->findBy(
            ['booking' => $booking],
            ['occurredAt' => 'ASC', 'id' => 'ASC']
        );

        self::assertIsArray($timelines);
        self::assertCount(3, $timelines, '3 transitions doivent produire 3 lignes de timeline');

        /** @var BookingTimeline $t1 */
        $t1 = $timelines[0];
        /** @var BookingTimeline $t2 */
        $t2 = $timelines[1];
        /** @var BookingTimeline $t3 */
        $t3 = $timelines[2];

        // Vérifie from -> to (le subscriber prend from via la transition et to via marking)
        self::assertNull($t1->getActor());
        self::assertSame('INQUIRY', $t1->getFromStatus());
        self::assertSame('CONTACTED', $t1->getToStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $t1->getOccurredAt());

        self::assertNull($t2->getActor());
        self::assertSame('CONTACTED', $t2->getFromStatus());
        self::assertSame('SCHEDULED', $t2->getToStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $t2->getOccurredAt());

        self::assertNull($t3->getActor());
        self::assertSame('SCHEDULED', $t3->getFromStatus());
        self::assertSame('DONE', $t3->getToStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $t3->getOccurredAt());
    }
}
