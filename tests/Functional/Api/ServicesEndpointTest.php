<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ServicesEndpointTest extends WebTestCase
{
    public function testListServicesBasic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/services');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $json = json_decode($client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('data', $json);
        self::assertArrayHasKey('meta', $json);
        self::assertSame(1, $json['meta']['page']);
        self::assertSame(20, $json['meta']['limit']);
        self::assertGreaterThanOrEqual(60, $json['meta']['total']);
        self::assertLessThanOrEqual($json['meta']['limit'], \count($json['data']));
    }

    public function testPaginationParams(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/services?page=2&limit=10');

        self::assertResponseIsSuccessful();
        $json = json_decode($client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame(2, $json['meta']['page']);
        self::assertSame(10, $json['meta']['limit']);
        self::assertLessThanOrEqual(10, \count($json['data']));
    }

    public function testFilterByCategorySlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/services?category=plomberie');

        self::assertResponseIsSuccessful();
        $json = json_decode($client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertNotEmpty($json['data']);

        foreach ($json['data'] as $row) {
            self::assertSame('plomberie', $row['category']['slug']);
        }
    }
}
