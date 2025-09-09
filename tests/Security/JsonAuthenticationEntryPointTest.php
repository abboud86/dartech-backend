<?php

declare(strict_types=1);

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JsonAuthenticationEntryPointTest extends WebTestCase
{
    public function testAnonymousGetsJson401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/private/ping', server: ['HTTP_X_REQUEST_ID' => 't-123']);

        $resp = $client->getResponse();
        self::assertSame(401, $resp->getStatusCode());
        self::assertStringContainsString('application/json', (string) $resp->headers->get('Content-Type'));

        $data = json_decode((string) $resp->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(401, $data['error']['code']);
        self::assertSame('Authentication required', $data['error']['message']);
        self::assertSame('t-123', $data['error']['request_id']);
    }
}
