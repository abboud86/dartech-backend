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

final class BookingTimelineEnrichmentTest extends WebTestCase
{
    /**
     * Cas 1 : création d’une booking via POST /api/bookings
     * -> la première ligne de timeline doit être créée
     * -> son contexte doit contenir les infos de création (canal, scheduled_at, estimated_amount).
     */
    public function testTimelineEnrichedOnBookingCreatedWithChannel(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // suffixe unique pour éviter toute collision (email, slug, titres…)
        $suffix = bin2hex(random_bytes(4));
        $email = "timeline-enrich-create+{$suffix}@example.test";

        // --- Arrange: graph minimal (User + Category + ServiceDefinition + ArtisanProfile + ArtisanService) ---
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x'); // peu importe la valeur en test
        $em->persist($user);

        $cat = (new Category())
            ->setName('Menuiserie '.$suffix)
            ->setSlug('menuiserie-'.$suffix);
        $em->persist($cat);

        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Pose fenêtre '.$suffix)
            ->setSlug('pose-fenetre-'.$suffix);
        $em->persist($sd);

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Menuisier '.$suffix)
            ->setPhone('+213555000'.random_int(100, 999))
            ->setWilaya('Alger')
            ->setCommune('El Biar');
        $em->persist($ap);

        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Pose fenêtre '.$suffix)
            ->setSlug('pose-fenetre-'.$suffix)
            ->setUnitAmount(150000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $em->flush();

        $scheduledAt = (new \DateTimeImmutable('+2 days'))->setTime(10, 0);
        $scheduledAtIso = $scheduledAt->format(\DateTimeInterface::ATOM);

        // --- Act: POST /api/bookings ---
        $client->request(
            'POST',
            '/api/bookings',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode([
                'artisan_service_id' => (string) $as->getId(),
                'communication_channel' => 'WHATSAPP',
                'scheduled_at' => $scheduledAtIso,
                'estimated_amount' => '75000',
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            201,
            $client->getResponse()->getStatusCode(),
            (string) $client->getResponse()->getContent()
        );
        self::assertJson($client->getResponse()->getContent());

        $payload = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('id', $payload);
        $bookingId = $payload['id'];
        self::assertIsString($bookingId);

        /** @var Booking|null $booking */
        $booking = $em->getRepository(Booking::class)->find($bookingId);
        self::assertInstanceOf(Booking::class, $booking);

        // --- Assert: une seule ligne de timeline avec contexte enrichi ---
        /** @var BookingTimeline[] $timelines */
        $timelines = $em->getRepository(BookingTimeline::class)->findBy(
            ['booking' => $booking],
            ['occurredAt' => 'ASC', 'id' => 'ASC']
        );

        self::assertCount(1, $timelines, 'La création doit produire exactement 1 entrée de timeline');

        $t = $timelines[0];
        $context = $t->getContext();

        self::assertIsArray($context, 'Le contexte de timeline ne doit pas être null');
        self::assertArrayHasKey('communication_channel', $context);
        self::assertArrayHasKey('scheduled_at', $context);
        self::assertArrayHasKey('estimated_amount', $context);

        self::assertSame('WHATSAPP', $context['communication_channel']);
        self::assertSame($scheduledAtIso, $context['scheduled_at']);
        self::assertSame('75000', (string) $context['estimated_amount']);
    }

    /**
     * Cas 2 : PATCH /api/bookings/{id} avec changement d’estimate
     * -> nouvelle ligne de timeline avec old/new dans le contexte.
     */
    public function testTimelineEnrichedOnEstimateChangeOldNew(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $email = "timeline-enrich-estimate+{$suffix}@example.test";

        // --- Arrange: graph + booking initiale via API ---
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        $cat = (new Category())
            ->setName('Plomberie '.$suffix)
            ->setSlug('plomberie-'.$suffix);
        $em->persist($cat);

        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Réparation fuite '.$suffix)
            ->setSlug('reparation-fuite-'.$suffix);
        $em->persist($sd);

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Plombier '.$suffix)
            ->setPhone('+213555001'.random_int(100, 999))
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Réparation fuite '.$suffix)
            ->setSlug('reparation-fuite-'.$suffix)
            ->setUnitAmount(5000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $em->flush();

        // Création initiale avec estimate = 5000
        $client->request(
            'POST',
            '/api/bookings',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode([
                'artisan_service_id' => (string) $as->getId(),
                'communication_channel' => 'PHONE_CALL',
                'estimated_amount' => 5000,
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            201,
            $client->getResponse()->getStatusCode(),
            (string) $client->getResponse()->getContent()
        );
        $payload = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $bookingId = $payload['id'];

        /** @var Booking|null $booking */
        $booking = $em->getRepository(Booking::class)->find($bookingId);
        self::assertInstanceOf(Booking::class, $booking);

        // Sanity: on part de la situation après création
        $initialTimelines = $em->getRepository(BookingTimeline::class)->findBy(
            ['booking' => $booking],
            ['occurredAt' => 'ASC', 'id' => 'ASC']
        );
        $initialCount = \count($initialTimelines);

        // --- Act: PATCH pour changer l’estimate 5000 -> 8000 ---
        $client->request(
            'PATCH',
            '/api/bookings/'.$bookingId,
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode([
                'estimated_amount' => 8000,
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            200,
            $client->getResponse()->getStatusCode(),
            (string) $client->getResponse()->getContent()
        );

        // --- Assert: une nouvelle entrée de timeline avec old/new ---
        /** @var BookingTimeline[] $timelines */
        $timelines = $em->getRepository(BookingTimeline::class)->findBy(
            ['booking' => $booking],
            ['occurredAt' => 'ASC', 'id' => 'ASC']
        );

        self::assertGreaterThan(
            $initialCount,
            \count($timelines),
            'Un changement d’estimate doit créer une nouvelle ligne de timeline'
        );

        $last = $timelines[\count($timelines) - 1];
        $ctx = $last->getContext();

        self::assertIsArray($ctx);
        self::assertArrayHasKey('estimated_amount_old', $ctx);
        self::assertArrayHasKey('estimated_amount_new', $ctx);

        self::assertSame('5000.00', (string) $ctx['estimated_amount_old']);
        self::assertSame('8000', (string) $ctx['estimated_amount_new']);
    }

    /**
     * Cas 3 : transition workflow appliquée (to_contacted / to_scheduled / to_done)
     * -> chaque transition produit une timeline avec un contexte indiquant la transition.
     */
    public function testTimelineEnrichedOnTransitionApplied(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $email = "timeline-enrich-transition+{$suffix}@example.test";

        // --- Arrange: graph + booking persistée directement ---
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        $cat = (new Category())
            ->setName('Carrelage '.$suffix)
            ->setSlug('carrelage-'.$suffix);
        $em->persist($cat);

        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Pose carrelage '.$suffix)
            ->setSlug('pose-carrelage-'.$suffix);
        $em->persist($sd);

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Carreleur '.$suffix)
            ->setPhone('+213555002'.random_int(100, 999))
            ->setWilaya('Alger')
            ->setCommune('Kouba');
        $em->persist($ap);

        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Pose carrelage '.$suffix)
            ->setSlug('pose-carrelage-'.$suffix)
            ->setUnitAmount(30000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $booking = (new Booking())
            ->setClient($user)
            ->setArtisanService($as);
        $em->persist($booking);
        $em->flush();

        $bookingId = (string) $booking->getId();

        // --- Act: appliquer 2 transitions via /api/bookings/{id}/transition ---
        // 1) to_contacted
        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_contacted'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // préparer scheduledAt (pour permettre SCHEDULED si règle métier)
        $booking = $em->getRepository(Booking::class)->find($bookingId);
        \assert($booking instanceof Booking);
        $booking->setScheduledAt((new \DateTimeImmutable('+3 days'))->setTime(9, 30));
        $em->flush();

        // 2) to_scheduled
        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_scheduled'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // --- Assert: il existe au moins 2 lignes timeline avec un contexte "transition" ---
        /** @var BookingTimeline[] $timelines */
        $timelines = $em->getRepository(BookingTimeline::class)->findBy(
            ['booking' => $booking],
            ['occurredAt' => 'ASC', 'id' => 'ASC']
        );

        self::assertGreaterThanOrEqual(2, \count($timelines));

        // On se concentre sur les deux dernières entrées, supposées correspondre aux 2 transitions.
        $last = $timelines[\count($timelines) - 1];
        $prev = $timelines[\count($timelines) - 2];

        $ctxPrev = $prev->getContext();
        $ctxLast = $last->getContext();

        self::assertIsArray($ctxPrev);
        self::assertIsArray($ctxLast);

        self::assertArrayHasKey('transition', $ctxPrev);
        self::assertArrayHasKey('transition', $ctxLast);

        self::assertSame('to_contacted', (string) $ctxPrev['transition']);
        self::assertSame('to_scheduled', (string) $ctxLast['transition']);
    }
}
