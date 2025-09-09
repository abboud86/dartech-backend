<?php

declare(strict_types=1);

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginThrottlingTest extends WebTestCase
{
    public function testLoginIsThrottledAfterFiveFailures(): void
    {
        $client = static::createClient();

        for ($i = 1; $i <= 5; ++$i) {
            $client->jsonRequest('POST', '/api/login', [
                'email' => 'nobody@example.test',
                'password' => 'wrong',
            ], server: ['HTTP_X_REQUEST_ID' => 't-'.$i]);

            self::assertSame(401, $client->getResponse()->getStatusCode(), 'Attempt '.$i.' should be 401');
        }

        $client->jsonRequest('POST', '/api/login', [
            'email' => 'nobody@example.test',
            'password' => 'wrong',
        ], server: ['HTTP_X_REQUEST_ID' => 't-6']);

        self::assertSame(429, $client->getResponse()->getStatusCode(), 'Attempt 6 should be 429');
    }
}
