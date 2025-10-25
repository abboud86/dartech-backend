<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookingListMeTest extends WebTestCase
{
    public function testListMeRequiresAuthenticationReturns401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/bookings/me');

        self::assertSame(401, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
    }

    public function testListMe200WithAuthAndEmptyList(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // Prépare un utilisateur de test (nettoyage + création)
        $email = 'bookingsme@example.test';
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('x'); // non utilisé par l’authenticator de test
        $em->persist($user);
        $em->flush();

        // Appel authentifié via en-tête X-Test-User (pattern de tes autres tests)
        $client->request('GET', '/api/bookings/me?page=1&size=2', server: [
            'HTTP_X_TEST_USER' => $email,
        ]);

        $res = $client->getResponse();
        self::assertSame(200, $res->getStatusCode(), (string) $res->getContent());
        self::assertJson($res->getContent());

        $data = \json_decode((string) $res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('page', $data);
        self::assertArrayHasKey('size', $data);
        self::assertArrayHasKey('total', $data);
        self::assertArrayHasKey('pages', $data);
        self::assertArrayHasKey('items', $data);

        self::assertSame(1, $data['page']);
        self::assertSame(2, $data['size']);
        self::assertIsInt($data['total']);
        self::assertIsInt($data['pages']);
        self::assertIsArray($data['items']); // peut être [] si aucun booking
    }
}
