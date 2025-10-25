<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookingTimelineListTest extends WebTestCase
{
    public function testTimelineRequiresAuthenticationReturns401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/bookings/01H0ABCDEF1234567890ABCDEF/timeline');

        self::assertSame(401, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
    }

    public function testTimeline200WithAuthAndEmptyList(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // Prépare l'utilisateur attendu par l'auth de test
        $email = 'timeline@example.test';
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('x'); // non utilisé par l'authenticator de test
        $em->persist($user);
        $em->flush();

        // Appel authentifié
        $client->request('GET', '/api/bookings/01H0ABCDEF1234567890ABCDEF/timeline?page=1&size=5', server: [
            'HTTP_X_TEST_USER' => $email,
        ]);

        $res = $client->getResponse();
        self::assertSame(200, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('page', $data);
        self::assertArrayHasKey('size', $data);
        self::assertArrayHasKey('items', $data);

        self::assertSame(1, $data['page']);
        self::assertSame(5, $data['size']);
        self::assertIsArray($data['items']); // vide si booking inexistant, OK pour ce smoke
    }
}
