<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthzTest extends WebTestCase
{
    public function test_liveness_and_readiness(): void
    {
        $client = static::createClient();
        $client->request('GET', '/healthz');

        $response = $client->getResponse();
        $status   = $response->getStatusCode();
        $this->assertContains($status, [200, 503], "Expected 200 or 503, got $status");

        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('time', $data);

        if (isset($data['checks'])) {
            $this->assertArrayHasKey('db', $data['checks']);
            $this->assertArrayHasKey('redis', $data['checks']);
            $this->assertContains($data['checks']['db'], ['up','down']);
            $this->assertContains($data['checks']['redis'], ['up','down']);
        }
    }
}
