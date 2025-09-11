<?php

declare(strict_types=1);

namespace App\Tests\Security;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginThrottlingTest extends WebTestCase
{
    public function testLoginIsThrottledAfterFiveFailures(): void
    {
        // Boot via client AVANT d'accéder au container
        $client = static::createClient();
        // Garder l'état en mémoire pour les 6 requêtes (ArrayAdapter en test)
        $client->disableReboot();

        // Purge explicite du pool pour un test déterministe
        $pool = static::getContainer()->get('cache.rate_limiter');
        \assert($pool instanceof CacheItemPoolInterface);
        $pool->clear();

        // IP fixe (clé globale stable)
        $server = [
            'REMOTE_ADDR' => '192.168.11.133',
            'HTTP_X_REQUEST_ID' => '',
        ];

        // Tentatives 1 → 5 : 401
        for ($i = 1; $i <= 5; ++$i) {
            $server['HTTP_X_REQUEST_ID'] = 't-'.$i;

            $client->jsonRequest(
                'POST',
                '/api/login',
                ['email' => 'nobody@example.test', 'password' => 'wrong'],
                server: $server
            );

            self::assertSame(401, $client->getResponse()->getStatusCode(), 'Attempt '.$i.' should be 401');
        }

        // 6e tentative : 429
        $server['HTTP_X_REQUEST_ID'] = 't-6';
        $client->jsonRequest(
            'POST',
            '/api/login',
            ['email' => 'nobody@example.test', 'password' => 'wrong'],
            server: $server
        );

        self::assertSame(429, $client->getResponse()->getStatusCode(), 'Attempt 6 should be 429');
    }
}
