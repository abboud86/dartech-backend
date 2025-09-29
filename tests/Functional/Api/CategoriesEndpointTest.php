<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CategoriesEndpointTest extends WebTestCase
{
    public function testListCategories(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/categories');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $payload = json_decode($client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey('data', $payload);
        self::assertIsArray($payload['data']);
        self::assertGreaterThanOrEqual(20, \count($payload['data']));

        $first = $payload['data'][0] ?? null;
        self::assertIsArray($first);
        self::assertArrayHasKey('id', $first);
        self::assertArrayHasKey('name', $first);
        self::assertArrayHasKey('slug', $first);
        self::assertArrayHasKey('parentId', $first);
    }
}
