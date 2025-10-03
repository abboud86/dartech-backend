<?php

namespace App\Tests\Functional\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginEmitsTokensTest extends WebTestCase
{
    public function testLoginEmitsAccessAndRefresh(): void
    {
        $client = static::createClient();
        $email = sprintf('artisan+%d@example.test', time());

        // register
        $client->request('POST', '/v1/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => 'ChangeMe!123',
        ]));
        self::assertResponseStatusCodeSame(201);

        // login
        $client->request('POST', '/v1/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => 'ChangeMe!123',
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('access_token', $data);
        self::assertArrayHasKey('refresh_token', $data);
        self::assertArrayHasKey('access_expires_at', $data);
        self::assertArrayHasKey('refresh_expires_at', $data);
        self::assertNotEmpty($data['access_token']);
        self::assertNotEmpty($data['refresh_token']);
    }
}
