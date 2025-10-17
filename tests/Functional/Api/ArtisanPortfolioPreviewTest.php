<?php

namespace App\Tests\Functional\Api;

use App\Entity\ArtisanProfile;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArtisanPortfolioPreviewTest extends WebTestCase
{
    private ?EntityManagerInterface $em = null;

    private function em(): EntityManagerInterface
    {
        if (!$this->em) {
            $this->em = static::getContainer()->get(EntityManagerInterface::class);
        }

        return $this->em;
    }

    private function truncateForIsolation(): void
    {
        $conn = $this->em()->getConnection();
        $platform = $conn->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $conn->executeStatement('TRUNCATE TABLE media RESTART IDENTITY CASCADE');
            $conn->executeStatement('TRUNCATE TABLE artisan_profile RESTART IDENTITY CASCADE');
            $conn->executeStatement('TRUNCATE TABLE "user" RESTART IDENTITY CASCADE');

            return;
        }

        if ($platform instanceof MySQLPlatform) {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            try {
                $conn->executeStatement('TRUNCATE TABLE `media`');
                $conn->executeStatement('TRUNCATE TABLE `artisan_profile`');
                $conn->executeStatement('TRUNCATE TABLE `user`');
            } finally {
                $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            }

            return;
        }

        $conn->executeStatement('DELETE FROM media');
        $conn->executeStatement('DELETE FROM artisan_profile');
        $conn->executeStatement('DELETE FROM "user"');
    }

    private function makeUser(string $email): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setPassword('test-password')
            ->setRoles([]);

        $this->em()->persist($user);

        return $user;
    }

    private function makeProfile(string $displayName, string $phone, string $wilaya, string $commune, string $email): ArtisanProfile
    {
        $user = $this->makeUser($email);

        $profile = (new ArtisanProfile())
            ->setDisplayName($displayName)
            ->setPhone($phone)
            ->setWilaya($wilaya)
            ->setCommune($commune);

        $profile->setUser($user);

        $this->em()->persist($profile);
        $this->em()->flush();

        return $profile;
    }

    public function testEmptyPortfolioReturnsEmptyArray(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->truncateForIsolation();

        $profile = $this->makeProfile(
            'Empty Portfolio',
            '+213555000001',
            'Alger',
            'Bab Ezzouar',
            'empty@example.test'
        );

        $client->request('GET', "/v1/artisans/{$profile->getSlug()}/portfolio/preview");

        $this->assertResponseIsSuccessful();
        $json = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertIsArray($json);
        $this->assertCount(0, $json);
    }

    public function testPublicOnlyLimit4OrderDesc(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->truncateForIsolation();

        $profile = $this->makeProfile(
            'Public Portfolio',
            '+213555000002',
            'Alger',
            'Hydra',
            'public4@example.test'
        );

        $urls = [];
        for ($i = 1; $i <= 5; ++$i) {
            $m = (new Media())
                ->setPublicUrl("https://cdn.example.test/public-$i.jpg")
                ->setIsPublic(true)
                ->setArtisanProfile($profile)
                ->setCreatedAt(new \DateTimeImmutable(sprintf('2025-01-0%d 10:00:00', $i)));
            $this->em()->persist($m);
            $urls[$i] = $m->getPublicUrl();
        }

        $this->em()->persist(
            (new Media())
                ->setPublicUrl('https://cdn.example.test/private-x.jpg')
                ->setIsPublic(false)
                ->setArtisanProfile($profile)
                ->setCreatedAt(new \DateTimeImmutable('2025-01-10 10:00:00'))
        );

        $this->em()->flush();

        $client->request('GET', "/v1/artisans/{$profile->getSlug()}/portfolio/preview");

        $this->assertResponseIsSuccessful();
        $json = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertIsArray($json);
        $this->assertCount(4, $json);

        $expected = [$urls[5], $urls[4], $urls[3], $urls[2]];
        $this->assertSame($expected, $json);
        $this->assertNotContains('https://cdn.example.test/private-x.jpg', $json);
    }

    public function testPrivateMediaAreIgnored(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->truncateForIsolation();

        $profile = $this->makeProfile(
            'Private Only',
            '+213555000003',
            'Alger',
            'El Harrach',
            'priv0@example.test'
        );

        for ($i = 1; $i <= 3; ++$i) {
            $this->em()->persist(
                (new Media())
                    ->setPublicUrl("https://cdn.example.test/hidden-$i.jpg")
                    ->setIsPublic(false)
                    ->setArtisanProfile($profile)
                    ->setCreatedAt(new \DateTimeImmutable(sprintf('2025-02-0%d 10:00:00', $i)))
            );
        }

        $this->em()->flush();

        $client->request('GET', "/v1/artisans/{$profile->getSlug()}/portfolio/preview");

        $this->assertResponseIsSuccessful();
        $json = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertIsArray($json);
        $this->assertSame([], $json, 'No private media should appear in preview');
    }
}
