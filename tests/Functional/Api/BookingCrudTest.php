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

final class BookingCrudTest extends WebTestCase
{
    public function testCreateBookingRequiresAuthenticationReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/bookings',
            server: [], // aucun header d'auth
            content: \json_encode([
                'artisan_service_id' => '01K9D4R9CJ2HPQQH7S3FYSJ1QA',
                'communication_channel' => 'WHATSAPP',
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(401, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('unauthorized', $data['error']);
    }

    public function testCreateBooking201WithMinimalPayload(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // --- Arrange : user + artisan service en base ---

        $suffix = bin2hex(random_bytes(4));
        $email = "booking-crud+{$suffix}@example.test";

        // Nettoyage éventuel de l’email
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User (client)
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x'); // non utilisé par l’auth de test
        $em->persist($user);

        // Catégorie + ServiceDefinition
        $cat = (new Category())
            ->setName('Plomberie '.$suffix)
            ->setSlug('plomberie-'.$suffix);
        $em->persist($cat);

        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie '.$suffix)
            ->setSlug('depannage-plomberie-'.$suffix);
        $em->persist($sd);

        // Profil artisan (marché Algérie : DZD + téléphone)
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Plombier')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Hussein Dey');
        $em->persist($ap);

        // Service artisan (tarif en DZD)
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention plomberie '.$suffix)
            ->setSlug('intervention-plomberie-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $em->flush();

        // --- Act : POST /api/bookings authentifié ---

        $client->request(
            'POST',
            '/api/bookings',
            server: [
                // header consommé par TestTokenAuthenticator
                'HTTP_X_TEST_USER' => $email,
            ],
            content: \json_encode([
                'artisan_service_id' => (string) $as->getId(),
                'communication_channel' => 'WHATSAPP',
                // scheduled_at / estimated_amount facultatifs au début
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(201, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        // Contrat minimal de réponse
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('status', $data);
        self::assertArrayHasKey('scheduled_at', $data);
        self::assertArrayHasKey('created_at', $data);
        self::assertArrayHasKey('updated_at', $data);

        self::assertSame('INQUIRY', $data['status']); // cohérent avec workflow initial_marking
        self::assertNull($data['updated_at']);        // création → pas encore de mise à jour
    }

    public function testGetBookingRequiresAuthenticationReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/bookings/01K9DUMMYIDNOTIMPORTANT');

        $res = $client->getResponse();
        self::assertSame(401, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('unauthorized', $data['error']);
    }

    public function testGetBooking400WhenInvalidBookingId(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $email = 'booking-get-invalid-id@example.test';

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User (client) pour que TestTokenAuthenticator puisse l'authentifier
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);
        $em->flush();

        $client->request(
            'GET',
            '/api/bookings/NOT_A_ULID',
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
        );

        $res = $client->getResponse();
        self::assertSame(400, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('invalid_booking_id', $data['error']);
    }

    public function testGetBooking404WhenNotFound(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $email = 'booking-get-notfound@example.test';

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);
        $em->flush();

        // ULID valide mais qui ne correspond à aucun booking
        $ulid = (string) new \Symfony\Component\Uid\Ulid();

        $client->request(
            'GET',
            '/api/bookings/'.$ulid,
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
        );

        $res = $client->getResponse();
        self::assertSame(404, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('booking_not_found', $data['error']);
    }

    public function testGetBooking200ReturnsBookingResource(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // --- Arrange : créer user + graph + booking ---

        $suffix = bin2hex(random_bytes(4));
        $email = "booking-get+{$suffix}@example.test";

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User (client)
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        // Catégorie
        $cat = (new Category())
            ->setName('Plomberie GET '.$suffix)
            ->setSlug('plomberie-get-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie GET '.$suffix)
            ->setSlug('depannage-plomberie-get-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Plombier GET')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention plomberie GET '.$suffix)
            ->setSlug('intervention-plomberie-get-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        // Booking
        $booking = new Booking();
        $booking->setClient($user);
        $booking->setArtisanService($as);
        $booking->setStatus(\App\Enum\BookingStatus::INQUIRY);
        // scheduledAt nul pour commencer
        $em->persist($booking);

        $em->flush();

        // --- Act : GET /api/bookings/{id} authentifié ---

        $client->request(
            'GET',
            '/api/bookings/'.(string) $booking->getId(),
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
        );

        $res = $client->getResponse();
        self::assertSame(200, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('status', $data);
        self::assertArrayHasKey('scheduled_at', $data);
        self::assertArrayHasKey('created_at', $data);
        self::assertArrayHasKey('updated_at', $data);

        self::assertSame((string) $booking->getId(), $data['id']);
        self::assertSame('INQUIRY', $data['status']);
    }

    public function testPatchBookingRequiresAuthenticationReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/bookings/01K9DUMMYIDNOTIMPORTANT',
            content: \json_encode([
                'communication_channel' => 'WHATSAPP',
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(401, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('unauthorized', $data['error']);
    }

    public function testPatchBooking400WhenInvalidJson(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $email = 'booking-patch-invalid-json@example.test';

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);
        $em->flush();

        $client->request(
            'PATCH',
            '/api/bookings/01K9DUMMYIDNOTIMPORTANT',
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
            // JSON volontairement invalide pour déclencher invalid_json
            content: '{invalid json',
        );

        $res = $client->getResponse();
        self::assertSame(400, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('invalid_json', $data['error']);
    }

    public function testPatchBooking400WhenInvalidBookingId(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $email = 'booking-patch-invalid-id@example.test';

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);
        $em->flush();

        $client->request(
            'PATCH',
            '/api/bookings/NOT_A_ULID',
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
            content: \json_encode([
                'communication_channel' => 'WHATSAPP',
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(400, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('invalid_booking_id', $data['error']);
    }

    public function testPatchBooking404WhenNotFound(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $email = 'booking-patch-notfound@example.test';

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);
        $em->flush();

        // ULID valide mais qui ne correspond à aucun booking
        $ulid = (string) new \Symfony\Component\Uid\Ulid();

        $client->request(
            'PATCH',
            '/api/bookings/'.$ulid,
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
            content: \json_encode([
                'communication_channel' => 'WHATSAPP',
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(404, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('booking_not_found', $data['error']);
    }

    public function testPatchBooking200UpdatesAllowedFields(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $email = "booking-patch+{$suffix}@example.test";

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User (client)
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        // Catégorie
        $cat = (new Category())
            ->setName('Plomberie PATCH '.$suffix)
            ->setSlug('plomberie-patch-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie PATCH '.$suffix)
            ->setSlug('depannage-plomberie-patch-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Plombier PATCH')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention plomberie PATCH '.$suffix)
            ->setSlug('intervention-plomberie-patch-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        // Booking initial
        $booking = new Booking();
        $booking->setClient($user);
        $booking->setArtisanService($as);
        $booking->setStatus(\App\Enum\BookingStatus::INQUIRY);
        $booking->setCommunicationChannel(\App\Enum\CommunicationChannel::PHONE_CALL);
        $em->persist($booking);

        $em->flush();

        $bookingId = (string) $booking->getId();
        $newScheduledAt = new \DateTimeImmutable('+1 day');

        // --- Act : PATCH /api/bookings/{id} authentifié ---

        $client->request(
            'PATCH',
            '/api/bookings/'.$bookingId,
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
            content: \json_encode([
                'communication_channel' => 'WHATSAPP',
                'scheduled_at' => $newScheduledAt->format(\DateTimeInterface::ATOM),
                'estimated_amount' => 35000,
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(200, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('status', $data);
        self::assertArrayHasKey('scheduled_at', $data);
        self::assertArrayHasKey('created_at', $data);
        self::assertArrayHasKey('updated_at', $data);

        self::assertSame($bookingId, $data['id']);
        self::assertSame('INQUIRY', $data['status']); // PATCH ne touche pas le workflow

        // Vérifier que scheduled_at a été mis à jour
        self::assertSame($newScheduledAt->format(\DateTimeInterface::ATOM), $data['scheduled_at']);

        // updated_at doit être non nul après un PATCH
        self::assertNotNull($data['updated_at']);
    }

    public function testCreateBooking422WhenScheduledAtInPast(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $email = "booking-create-past+{$suffix}@example.test";

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User (client)
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        // Catégorie
        $cat = (new Category())
            ->setName('Plomberie CREATE PAST '.$suffix)
            ->setSlug('plomberie-create-past-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie CREATE PAST '.$suffix)
            ->setSlug('depannage-plomberie-create-past-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Plombier CREATE PAST')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention plomberie CREATE PAST '.$suffix)
            ->setSlug('intervention-plomberie-create-past-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $em->flush();

        $past = (new \DateTimeImmutable('-1 day'))->format(\DateTimeInterface::ATOM);

        $client->request(
            'POST',
            '/api/bookings',
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
            content: \json_encode([
                'artisan_service_id' => (string) $as->getId(),
                'communication_channel' => 'WHATSAPP',
                'scheduled_at' => $past,
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(422, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('invalid_scheduled_at_business', $data['error']);
    }

    public function testCreateBooking422WhenEstimatedAmountOutOfRange(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $email = "booking-create-amount+{$suffix}@example.test";

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User (client)
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        // Catégorie
        $cat = (new Category())
            ->setName('Plomberie CREATE AMOUNT '.$suffix)
            ->setSlug('plomberie-create-amount-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie CREATE AMOUNT '.$suffix)
            ->setSlug('depannage-plomberie-create-amount-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Plombier CREATE AMOUNT')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention plomberie CREATE AMOUNT '.$suffix)
            ->setSlug('intervention-plomberie-create-amount-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        $em->flush();

        // Montant hors plage métier (trop bas)
        $outOfRangeAmount = 100;

        $client->request(
            'POST',
            '/api/bookings',
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
            content: \json_encode([
                'artisan_service_id' => (string) $as->getId(),
                'communication_channel' => 'WHATSAPP',
                'estimated_amount' => $outOfRangeAmount,
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(422, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('invalid_estimated_amount_business', $data['error']);
    }

    public function testPatchBooking422WhenScheduledAtInPast(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $email = "booking-patch-past+{$suffix}@example.test";

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User (client)
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        // Catégorie
        $cat = (new Category())
            ->setName('Plomberie PATCH PAST '.$suffix)
            ->setSlug('plomberie-patch-past-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie PATCH PAST '.$suffix)
            ->setSlug('depannage-plomberie-patch-past-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Plombier PATCH PAST')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention plomberie PATCH PAST '.$suffix)
            ->setSlug('intervention-plomberie-patch-past-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        // Booking initial
        $booking = new Booking();
        $booking->setClient($user);
        $booking->setArtisanService($as);
        $booking->setStatus(\App\Enum\BookingStatus::INQUIRY);
        $booking->setCommunicationChannel(\App\Enum\CommunicationChannel::PHONE_CALL);
        $em->persist($booking);

        $em->flush();

        $bookingId = (string) $booking->getId();
        $past = (new \DateTimeImmutable('-1 day'))->format(\DateTimeInterface::ATOM);

        $client->request(
            'PATCH',
            '/api/bookings/'.$bookingId,
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
            content: \json_encode([
                'scheduled_at' => $past,
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(422, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('invalid_scheduled_at_business', $data['error']);
    }

    public function testPatchBooking422WhenEstimatedAmountOutOfRange(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $email = "booking-patch-amount+{$suffix}@example.test";

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User (client)
        $user = (new User())
            ->setEmail($email)
            ->setPassword('x');
        $em->persist($user);

        // Catégorie
        $cat = (new Category())
            ->setName('Plomberie PATCH AMOUNT '.$suffix)
            ->setSlug('plomberie-patch-amount-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie PATCH AMOUNT '.$suffix)
            ->setSlug('depannage-plomberie-patch-amount-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile
        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Artisan Plombier PATCH AMOUNT')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention plomberie PATCH AMOUNT '.$suffix)
            ->setSlug('intervention-plomberie-patch-amount-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        // Booking initial
        $booking = new Booking();
        $booking->setClient($user);
        $booking->setArtisanService($as);
        $booking->setStatus(\App\Enum\BookingStatus::INQUIRY);
        $booking->setCommunicationChannel(\App\Enum\CommunicationChannel::PHONE_CALL);
        $em->persist($booking);

        $em->flush();

        $bookingId = (string) $booking->getId();

        // Montant hors plage métier (trop bas)
        $outOfRangeAmount = 100;

        $client->request(
            'PATCH',
            '/api/bookings/'.$bookingId,
            server: [
                'HTTP_X_TEST_USER' => $email,
            ],
            content: \json_encode([
                'estimated_amount' => $outOfRangeAmount,
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(422, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('invalid_estimated_amount_business', $data['error']);
    }

    public function testGetBooking403WhenNotOwner(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $ownerEmail = "booking-owner+{$suffix}@example.test";
        $otherEmail = "booking-other+{$suffix}@example.test";

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email IN (:e)')
            ->setParameter('e', [$ownerEmail, $otherEmail])
            ->execute();

        // User propriétaire
        $owner = (new User())
            ->setEmail($ownerEmail)
            ->setPassword('x');
        $em->persist($owner);

        // User non propriétaire
        $other = (new User())
            ->setEmail($otherEmail)
            ->setPassword('x');
        $em->persist($other);

        // Catégorie
        $cat = (new Category())
            ->setName('Plomberie OWNERSHIP '.$suffix)
            ->setSlug('plomberie-ownership-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie OWNERSHIP '.$suffix)
            ->setSlug('depannage-plomberie-ownership-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile (lié au owner, peu importe pour le test)
        $ap = (new ArtisanProfile())
            ->setUser($owner)
            ->setDisplayName('Artisan OWNERSHIP')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention OWNERSHIP '.$suffix)
            ->setSlug('intervention-ownership-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        // Booking appartenant au owner
        $booking = new Booking();
        $booking->setClient($owner);
        $booking->setArtisanService($as);
        $booking->setStatus(\App\Enum\BookingStatus::INQUIRY);
        $booking->setCommunicationChannel(\App\Enum\CommunicationChannel::PHONE_CALL);
        $em->persist($booking);

        $em->flush();

        $bookingId = (string) $booking->getId();

        // Act : GET avec l'autre utilisateur (non propriétaire)
        $client->request(
            'GET',
            '/api/bookings/'.$bookingId,
            server: [
                'HTTP_X_TEST_USER' => $otherEmail,
            ]
        );

        $res = $client->getResponse();
        self::assertSame(403, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('forbidden', $data['error']);
    }

    public function testPatchBooking403WhenNotOwner(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $suffix = bin2hex(random_bytes(4));
        $ownerEmail = "booking-patch-owner+{$suffix}@example.test";
        $otherEmail = "booking-patch-other+{$suffix}@example.test";

        // Clean éventuel
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email IN (:e)')
            ->setParameter('e', [$ownerEmail, $otherEmail])
            ->execute();

        // User propriétaire
        $owner = (new User())
            ->setEmail($ownerEmail)
            ->setPassword('x');
        $em->persist($owner);

        // User non propriétaire
        $other = (new User())
            ->setEmail($otherEmail)
            ->setPassword('x');
        $em->persist($other);

        // Catégorie
        $cat = (new Category())
            ->setName('Plomberie PATCH OWNERSHIP '.$suffix)
            ->setSlug('plomberie-patch-ownership-'.$suffix);
        $em->persist($cat);

        // ServiceDefinition
        $sd = (new ServiceDefinition())
            ->setCategory($cat)
            ->setName('Dépannage plomberie PATCH OWNERSHIP '.$suffix)
            ->setSlug('depannage-plomberie-patch-ownership-'.$suffix);
        $em->persist($sd);

        // ArtisanProfile
        $ap = (new ArtisanProfile())
            ->setUser($owner)
            ->setDisplayName('Artisan PATCH OWNERSHIP')
            ->setPhone('+213555000000')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar');
        $em->persist($ap);

        // ArtisanService
        $as = (new ArtisanService())
            ->setArtisanProfile($ap)
            ->setServiceDefinition($sd)
            ->setTitle('Intervention PATCH OWNERSHIP '.$suffix)
            ->setSlug('intervention-patch-ownership-'.$suffix)
            ->setUnitAmount(20000)
            ->setCurrency('DZD')
            ->setStatus(ArtisanServiceStatus::DRAFT);
        $em->persist($as);

        // Booking appartenant au owner
        $booking = new Booking();
        $booking->setClient($owner);
        $booking->setArtisanService($as);
        $booking->setStatus(\App\Enum\BookingStatus::INQUIRY);
        $booking->setCommunicationChannel(\App\Enum\CommunicationChannel::PHONE_CALL);
        $em->persist($booking);

        $em->flush();

        $bookingId = (string) $booking->getId();

        // Act : PATCH avec l'autre utilisateur (non propriétaire)
        $client->request(
            'PATCH',
            '/api/bookings/'.$bookingId,
            server: [
                'HTTP_X_TEST_USER' => $otherEmail,
            ],
            content: \json_encode([
                'estimated_amount' => 70000,
            ], \JSON_THROW_ON_ERROR)
        );

        $res = $client->getResponse();
        self::assertSame(403, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('forbidden', $data['error']);
    }
}
