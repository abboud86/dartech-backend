<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookingTransitionApiTest extends WebTestCase
{
    public function testTransitionRequiresAuthenticationReturns401(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/bookings/01H0ABCDEF1234567890ABCDEF/transition', content: '{}');

        self::assertSame(401, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
    }

    public function testTransition400WhenMissingTransition(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $email = 'transition-missing@example.test';
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')->setParameter('e', $email)->execute();

        $user = (new User())->setEmail($email)->setPassword('x');
        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/bookings/01H0ABCDEF1234567890ABCDEF/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: '{}'
        );

        self::assertSame(400, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('missing_transition', $data['error'] ?? null);
    }

    public function testTransition400WhenInvalidBookingId(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $email = 'transition-invalidid@example.test';
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')->setParameter('e', $email)->execute();

        $user = (new User())->setEmail($email)->setPassword('x');
        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/bookings/NOT_A_ULID/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_done'], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(400, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('invalid_booking_id', $data['error'] ?? null);
    }

    public function testTransition404WhenBookingNotFound(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $email = 'transition-notfound@example.test';
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')->setParameter('e', $email)->execute();

        $user = (new User())->setEmail($email)->setPassword('x');
        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/bookings/01H0ABCDEF1234567890ABCDEF/transition',
            server: ['HTTP_X_TEST_USER' => $email],
            content: \json_encode(['transition' => 'to_done'], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(404, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('booking_not_found', $data['error'] ?? null);
    }
}
