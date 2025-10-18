<?php

declare(strict_types=1);

namespace App\Tests\Functional\Internal;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArtisanListSmokeTest extends WebTestCase
{
    public function testListPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_internal/artisans');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Artisans (KYC = verified • services publiés ≥ 1)', $client->getResponse()->getContent());
    }
}
