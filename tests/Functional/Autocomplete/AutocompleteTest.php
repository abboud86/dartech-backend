<?php

declare(strict_types=1);

namespace App\Tests\Functional\Autocomplete;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AutocompleteTest extends WebTestCase
{
    public function testCities400WhenQueryTooShort(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/autocomplete/cities?q=a');

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent() ?: '[]', true);
        self::assertSame('invalid_query', $data['error'] ?? null);
    }

    public function testCities200WithResultsLimit10(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/autocomplete/cities?q=al');

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent() ?: '[]', true);
        self::assertIsArray($data);
        self::assertLessThanOrEqual(10, \count($data));
        foreach ($data as $city) {
            self::assertIsString($city);
            self::assertNotSame('', trim($city));
        }
    }

    public function testCategories400WhenQueryTooShort(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/autocomplete/categories?q=z');

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent() ?: '[]', true);
        self::assertSame('invalid_query', $data['error'] ?? null);
    }

    public function testCategories200WithResultsLimit10(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/autocomplete/categories?q=pl');

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent() ?: '[]', true);
        self::assertIsArray($data);
        self::assertLessThanOrEqual(10, \count($data));
        foreach ($data as $slug) {
            self::assertIsString($slug);
            self::assertNotSame('', trim($slug));
        }
    }
}
