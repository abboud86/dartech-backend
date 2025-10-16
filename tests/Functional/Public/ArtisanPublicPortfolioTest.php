<?php

declare(strict_types=1);

namespace App\Tests\Functional\Public;

use App\Entity\ArtisanProfile;
use App\Entity\User;
use App\Enum\KycStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArtisanPublicPortfolioTest extends WebTestCase
{
    public function testPortfolioPreviewIsPresentAndArray(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // User + ArtisanProfile VERIFIED
        $user = new User();
        $user->setEmail('artisan.portfolio+'.str_replace('.', '', uniqid('', true)).'@test.local');
        $user->setPassword('dummy-hash');
        $em->persist($user);

        $ap = new ArtisanProfile();
        $ap->setDisplayName('Ali B.');
        $ap->setPhone('+213555555555');
        $ap->setWilaya('Alger');
        $ap->setCommune('Alger-Centre');
        $ap->setKycStatus(KycStatus::VERIFIED);
        $ap->setUser($user);
        $em->persist($ap);
        $em->flush();

        $publicId = (string) $user->getId();

        // Appel endpoint public
        $client->request('GET', '/v1/artisans/'.$publicId);
        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        // ✅ portfolioPreview présent et array (actuellement vide)
        self::assertArrayHasKey('portfolioPreview', $data);
        self::assertIsArray($data['portfolioPreview']);
        self::assertSame([], $data['portfolioPreview']);
    }
}
