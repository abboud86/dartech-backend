<?php

namespace App\Tests\Functional\Auth;

use App\Entity\User;
use App\Security\TokenIssuer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LogoutAllTest extends WebTestCase
{
    public function testLogoutAllReturns401WithoutAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/v1/auth/logout/all');

        self::assertSame(401, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent() ?: '');
        self::assertJson($client->getResponse()->getContent());
    }

    public function testLogoutAllRevokesAllAndIsIdempotent(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        /** @var TokenIssuer $issuer */
        $issuer = $container->get(TokenIssuer::class);

        $email = 'logoutall@example.test';

        // Clean in dependency order: tokens -> user
        $em->createQuery(
            'DELETE FROM App\Entity\AccessToken at
             WHERE at.owner IN (SELECT u FROM App\Entity\User u WHERE u.email = :e)'
        )->setParameter('e', $email)->execute();

        $em->createQuery(
            'DELETE FROM App\Entity\RefreshToken rt
             WHERE rt.owner IN (SELECT u FROM App\Entity\User u WHERE u.email = :e)'
        )->setParameter('e', $email)->execute();

        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', $email)
            ->execute();

        // User
        $user = (new User())->setEmail($email)->setPassword('x'); // password unused by test auth
        $em->persist($user);
        $em->flush();

        // Two sessions (2 couples access/refresh)
        $r1 = $issuer->issue($user, [], new \DateInterval('PT15M'), new \DateInterval('P30D'));
        $issuer->issue($user, [], new \DateInterval('PT15M'), new \DateInterval('P30D'));

        // Authenticated call via test header
        $client->request('POST', '/v1/auth/logout/all', server: ['HTTP_X_TEST_USER' => $email]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent() ?: '');
        $data = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('revoked_all', $data['status']);
        self::assertGreaterThanOrEqual(1, $data['refresh_revoked']);
        self::assertGreaterThanOrEqual(1, $data['access_revoked']);

        // The first refresh token must now be invalid
        $client->request(
            'POST',
            '/v1/auth/token/refresh',
            server: ['HTTP_X_TEST_USER' => $email],
            content: json_encode(['refresh_token' => $r1['refresh_token']], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(401, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent() ?: '');

        // Idempotent: 2nd call => 0 revoked
        $client->request('POST', '/v1/auth/logout/all', server: ['HTTP_X_TEST_USER' => $email]);
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data2 = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(0, $data2['refresh_revoked']);
        self::assertSame(0, $data2['access_revoked']);
    }
}
