<?php

declare(strict_types=1);

namespace App\Tests\Functional\Internal;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InternalSmokeTest extends WebTestCase
{
    public function testHealth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_internal/_health');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('ok', $data['status'] ?? null);
    }

    public function testVersion(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_internal/_version');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('version', $data);
        self::assertArrayHasKey('env', $data);
        self::assertArrayHasKey('time', $data);
    }

    public function testKpis(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_internal/_kpis');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('users_total', $data);
        self::assertArrayHasKey('artisans_verified', $data);
        self::assertArrayHasKey('services_published', $data);
        self::assertIsInt($data['users_total']);
        self::assertIsInt($data['artisans_verified']);
        self::assertIsInt($data['services_published']);
    }
}
