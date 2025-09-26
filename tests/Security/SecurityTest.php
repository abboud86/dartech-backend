<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityTest extends WebTestCase
{
    public function testUnauthorizedJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/ping');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode(), 'Expected 401');

        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json; charset=UTF-8'),
            'Content-Type must be application/json; charset=UTF-8'
        );

        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('unauthorized', $data['error']);
    }

    public function testLoginThrottlingIsWired(): void
    {
        $container = static::getContainer();

        // Listener de throttling du firewall "main"
        $this->assertTrue(
            $container->has('security.listener.login_throttling.main'),
            'Listener security.listener.login_throttling.main should exist'
        );

        // Limiter côté sécurité (id peut varier selon version) → on accepte plusieurs alias connus
        $hasLimiter =
            $container->has('security.login_throttling.main.limiter') ||
            $container->has('security.listener.login_throttling.request_rate_limiter') ||
            $container->has('security.rate_limiter.login');

        $this->assertTrue($hasLimiter, 'Login throttling limiter service should be registered');
    }
}
