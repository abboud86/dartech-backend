<?php

declare(strict_types=1);

namespace App\Tests\Functional\Public;

use App\Entity\ArtisanProfile;
use App\Entity\User;
use App\Enum\KycStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

final class ArtisanPublicControllerTest extends WebTestCase
{
    public function testShowReturns404WhenSlugNotFound(): void
    {
        $client = static::createClient();

        // ULID valide mais inexistant → 404 propre (évite l'erreur SQL)
        $unknown = (string) new Ulid();

        $client->request('GET', '/v1/artisans/'.$unknown);

        self::assertResponseStatusCodeSame(404);
        self::assertJson($client->getResponse()->getContent());
    }

    public function testShowReturns200ForExistingPublicArtisan(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // 1) Créer un User (ULID auto) + champs requis
        $user = new User();
        $user->setEmail('artisan.public+'.str_replace('.', '', uniqid('', true)).'@test.local'); // ✅ unique
        $user->setPassword('dummy-hash');
        $em->persist($user);

        // 2) Créer un ArtisanProfile VERIFIED (KYC)
        $ap = new ArtisanProfile();
        $ap->setDisplayName('Ali B.');
        $ap->setPhone('+213555555555'); // E.164 valide
        $ap->setWilaya('Alger');
        $ap->setCommune('Alger-Centre');
        $ap->setKycStatus(KycStatus::VERIFIED);
        $ap->setUser($user);
        $em->persist($ap);

        $em->flush();
        $publicId = (string) $user->getId(); // {slug} provisoire = User.id (ULID)

        // 3) Appeler l’endpoint
        $client->request('GET', '/v1/artisans/'.$publicId);

        // 4) Asserts
        self::assertResponseIsSuccessful(); // 200
        self::assertResponseHasHeader('Cache-Control');
        $cc = $client->getResponse()->headers->get('Cache-Control');
        self::assertIsString($cc);
        self::assertStringContainsString('public', $cc);
        self::assertStringContainsString('max-age=300', $cc);
        self::assertStringContainsString('s-maxage=600', $cc);

        $json = $client->getResponse()->getContent();
        self::assertJson($json);
        $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        // Clés minimales attendues par le contrat public
        self::assertSame('Ali B.', $data['displayName'] ?? null);
        self::assertSame('Alger-Centre', $data['city'] ?? null);
        self::assertIsBool($data['verified'] ?? null);
        self::assertArrayHasKey('services', $data);
        self::assertArrayHasKey('portfolioPreview', $data);
        self::assertArrayHasKey('updatedAt', $data);
    }
}
