<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }

    public function load(ObjectManager $manager): void
    {
        $repo = $manager->getRepository(Category::class);

        $rows = [
            ['name' => 'Plomberie',     'description' => 'Installation et dépannage plomberie'],
            ['name' => 'Électricité',   'description' => 'Courant fort/faible, dépannages'],
            ['name' => 'Peinture',      'description' => 'Intérieur/extérieur'],
            ['name' => 'Menuiserie',    'description' => 'Bois & aluminium'],
            ['name' => 'Climatisation', 'description' => 'Pose et maintenance'],
            ['name' => 'Maçonnerie',    'description' => 'Rénovation & gros œuvre'],
            ['name' => 'Serrurerie',    'description' => 'Ouverture & sécurisation'],
            ['name' => 'Jardinage',     'description' => 'Entretien des espaces verts'],
        ];

        foreach ($rows as $r) {
            $name = $r['name'];
            $slug = $this->slugger->slug($name)->lower()->toString();

            $category = $repo->findOneBy(['slug' => $slug]) ?? new Category();
            $category->setName($name);
            $category->setSlug($slug);
            $category->setDescription($r['description']);

            $manager->persist($category);
        }

        $manager->flush();
    }
}
