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

final class BookingTimelineListApiTest extends WebTestCase
{
    public function testListTimelineReturns200AndItemsWithPagination(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // Suffixe unique
        $suffix = bin2hex(random_bytes(4));
        $email = "hp-timeline-list+{$suffix}@example.test";

        // --- Arrange minimal graph ---
        $user = (new User())->setEmail($email)->setPassword('x');
        $em->persist($user);

        $cat = (new Category())
            ->setName('Menuiserie '.$suffix)
            ->setSlug('menuiserie-'.$suffix);
        $em->persist($cat);

        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Pose poignée '.$suffix)
            ->setSlug('pose-poignee-'.$suffix);
        $em->persist($sd);

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Menui')
            ->setPhone('+213555000111')
            ->setWilaya('Alger')
            ->setCommune('Hydra');
        $em->persist($ap);

        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Pose poignée '.$suffix)
            ->setSlug('pose-poignee-svc-'.$suffix)
            ->setUnitAmount(12000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $booking = (new Booking())
            ->setClient($user)
            ->setArtisanService($as);
        $em->persist($booking);
        $em->flush();

        $bookingId = (string) $booking->getId();

        // 2 transitions = 2 entrées attendues dans la timeline
        // to_contacted
        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_contacted'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // préparer scheduledAt puis to_scheduled
        $booking = $em->getRepository(Booking::class)->find($bookingId);
        \assert($booking instanceof Booking);
        $booking->setScheduledAt((new \DateTimeImmutable('+1 day'))->setTime(8, 0));
        $em->flush();

        $client->request(
            'POST',
            "/api/bookings/{$bookingId}/transition",
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_scheduled'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        // Sanity check DB: 2 timelines
        $all = $em->getRepository(BookingTimeline::class)->findBy(
            ['booking' => $booking],
            ['occurredAt' => 'ASC', 'id' => 'ASC']
        );
        self::assertCount(2, $all);

        // --- Act: GET /api/bookings/{id}/timeline?page=1&size=10 ---
        $client->request(
            'GET',
            "/api/bookings/{$bookingId}/timeline?page=1&size=10",
            server: ['HTTP_X_TEST_USER' => $email],
        );

        // --- Assert: 200 + payload shape ---
        $res = $client->getResponse();
        self::assertSame(200, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        // Contrat minimal
        self::assertIsArray($data);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('page', $data);
        self::assertArrayHasKey('size', $data);
        self::assertArrayHasKey('total', $data);

        // Pagination et contenu
        self::assertSame(1, $data['page']);
        self::assertSame(10, $data['size']);
        self::assertSame(2, $data['total']);
        self::assertIsArray($data['items']);
        self::assertCount(2, $data['items']);

        // Items triés ASC par occurredAt
        $first = $data['items'][0];
        $second = $data['items'][1];

        foreach ([$first, $second] as $row) {
            self::assertArrayHasKey('id', $row);
            self::assertArrayHasKey('from', $row);
            self::assertArrayHasKey('to', $row);
            self::assertArrayHasKey('occurred_at', $row);
            // actor peut être null → optionnel, ne pas forcer
        }

        self::assertSame('INQUIRY', $first['from']);
        self::assertSame('CONTACTED', $first['to']);

        self::assertSame('CONTACTED', $second['from']);
        self::assertSame('SCHEDULED', $second['to']);
    }
}
