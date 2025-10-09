<?php

declare(strict_types=1);

namespace App\Tests\Functional\Artisan;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArtisanSearchTest extends WebTestCase
{
    public function testItReturns200WithDefaults(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/artisans'); // defaults page=1, per_page=20

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $json = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertSame(1, $json['meta']['page']);
        $this->assertSame(20, $json['meta']['per_page']);
        $this->assertSame(0, $json['meta']['total']);
    }

    public function testItReturns400OnInvalidParams(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/artisans?page=0&per_page=1000'); // invalid per our constraints

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $json = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('errors', $json);
        $this->assertIsArray($json['errors']);
        $this->assertNotEmpty($json['errors']);
    }
}
