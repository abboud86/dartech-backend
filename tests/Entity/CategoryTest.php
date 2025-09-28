<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\ServiceDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CategoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $this->em = $em;
    }

    public function testPersistCategoryAndServiceDefinition(): void
    {
        $category = (new Category())
            ->setName('Plomberie')
            ->setSlug('plomberie-'.uniqid());

        $service = (new ServiceDefinition())
            ->setCategory($category)
            ->setName('Débouchage')
            ->setSlug('debouchage-'.uniqid())
            ->setAttributesSchema(['type' => 'forfait']);

        $this->em->persist($category);
        $this->em->persist($service);
        $this->em->flush();

        $this->assertNotNull($category->getId(), 'Category ULID should be set after flush');
        $this->assertNotNull($service->getId(), 'ServiceDefinition ULID should be set after flush');
        $this->assertSame($category, $service->getCategory(), 'ServiceDefinition must reference the same Category instance');
    }

    protected function tearDown(): void
    {
        $this->em->clear();
        parent::tearDown();
    }
}
