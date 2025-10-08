<?php

namespace App\Tests\Functional;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\ServiceDefinition;
use App\Enum\ArtisanServiceStatus;
use App\Repository\ArtisanServiceRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ArtisanServicePublicationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    private function makeService(): ArtisanService
    {
        $artisan = new ArtisanProfile();
        $def = new ServiceDefinition();

        $s = new ArtisanService();
        $s->setArtisanProfile($artisan);
        $s->setServiceDefinition($def);
        $s->setTitle('Titre');
        $s->setSlug('slug-test');
        $s->setUnitAmount(1000);
        $s->setCurrency('DZD');

        return $s;
    }

    public function testAllowsSingleActivePerCouple(): void
    {
        // Mock repository => aucune offre ACTIVE existante pour ce couple
        $repo = $this->createMock(ArtisanServiceRepository::class);
        $repo->method('hasActiveForCouple')->willReturn(false);
        static::getContainer()->set(ArtisanServiceRepository::class, $repo);

        $s = $this->makeService();
        $s->setStatus(ArtisanServiceStatus::ACTIVE);
        $s->setPublishedAt(new \DateTimeImmutable());

        $violations = $this->validator->validate($s);
        self::assertCount(0, $violations, 'A single ACTIVE service for a couple should be allowed.');
    }

    public function testBlocksSecondActiveSameCouple(): void
    {
        // Mock repository => il existe déjà UNE offre ACTIVE pour ce couple
        $repo = $this->createMock(ArtisanServiceRepository::class);
        $repo->method('hasActiveForCouple')->willReturn(true);
        static::getContainer()->set(ArtisanServiceRepository::class, $repo);

        $s = $this->makeService();
        $s->setStatus(ArtisanServiceStatus::ACTIVE);
        $s->setPublishedAt(new \DateTimeImmutable());

        $violations = $this->validator->validate($s);
        self::assertGreaterThanOrEqual(1, $violations->count(), 'Second ACTIVE service should raise a violation.');
        // Optionnel: vérifier qu’on cible bien "status"
        $this->assertSame('status', $violations[0]->getPropertyPath());
    }
}
