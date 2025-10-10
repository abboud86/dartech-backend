<?php

declare(strict_types=1);

namespace App\Tests\Functional\Artisan;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArtisanSearchSortTest extends WebTestCase
{
    public function testSortRecentReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/artisans?sort=recent&page=1&per_page=5');

        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $json = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($json['data'] ?? null);
        self::assertIsArray($json['meta'] ?? null);
        self::assertSame('recent', $json['meta']['sort'] ?? null);
        self::assertIsInt($json['meta']['total'] ?? null);
    }

    public function testSortRelevanceIsDefault(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/artisans?page=1&per_page=5'); // pas de sort => relevance

        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $json = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('relevance', $json['meta']['sort'] ?? null);
        self::assertIsInt($json['meta']['total'] ?? null);
    }

    public function testInvalidSortReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/artisans?sort=invalid_value');

        self::assertSame(400, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $json = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($json['errors'] ?? null);
        self::assertNotEmpty($json['errors']);
    }

    public function testPaginationDefensive(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/artisans?page=1&per_page=100');

        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $json = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(1, $json['meta']['page'] ?? null);
        self::assertSame(100, $json['meta']['per_page'] ?? null);
        self::assertIsInt($json['meta']['total'] ?? null);
    }
}
