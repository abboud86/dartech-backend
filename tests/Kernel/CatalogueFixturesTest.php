<?php

declare(strict_types=1);

namespace App\Tests\Kernel;

use App\Repository\CategoryRepository;
use App\Repository\ServiceDefinitionRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CatalogueFixturesTest extends KernelTestCase
{
    public function testCatalogueCountsAndRelations(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        /** @var CategoryRepository $catRepo */
        $catRepo = $container->get(CategoryRepository::class);
        /** @var ServiceDefinitionRepository $svcRepo */
        $svcRepo = $container->get(ServiceDefinitionRepository::class);

        $nbCategories = $catRepo->count([]);
        $nbServices   = $svcRepo->count([]);

        // Seuils issus du plan (≥20 catégories, ≥60 services)
        self::assertGreaterThanOrEqual(20, $nbCategories, 'Expected at least 20 categories');
        self::assertGreaterThanOrEqual(60, $nbServices, 'Expected at least 60 services');

        // Vérifie qu’au moins un service est lié à une catégorie non nulle
        $one = $svcRepo->findOneBy([]);
        self::assertNotNull($one, 'Expected at least one service');
        self::assertNotNull($one->getCategory(), 'ServiceDefinition::category must not be null');
    }
}
