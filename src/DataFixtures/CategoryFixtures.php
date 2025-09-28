<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public const REF_PREFIX = 'cat:';

    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public static function getGroups(): array
    {
        return ['catalogue'];
    }

    public function load(ObjectManager $manager): void
    {
        /**
         * Hiérarchie : parent => [children...]
         * (20 catégories au total, avec quelques sous-catégories pour démontrer la hiérarchie).
         */
        $tree = [
            'Plomberie' => ['Dépannage plomberie', 'Installation sanitaire', 'Chauffe-eau'],
            'Électricité' => ['Dépannage élec', 'Tableau électrique', 'Éclairage'],
            'Climatisation & Froid' => ['Pose split', 'Entretien climatiseur'],
            'Maçonnerie' => ['Gros œuvre', 'Petits travaux'],
            'Peinture & Décoration' => ['Peinture intérieure', 'Peinture extérieure'],
            'Menuiserie Bois' => ['Portes & Fenêtres', 'Dressings'],
            'Menuiserie Aluminium & PVC' => ['Fenêtres alu/PVC', 'Vérandas'],
            'Serrurerie' => ['Ouverture porte', 'Remplacement serrures'],
            'Carrelage & Sols' => ['Pose carrelage', 'Parquet & stratifié'],
            'Plâtre & Faux plafonds' => ['Cloisons', 'Faux plafond'],
            'Étanchéité & Isolation' => ['Étanchéité toitures', 'Isolation thermique'],
            'Jardinage & Espaces verts' => ['Entretien jardin', 'Arrosage'],
            'Nettoyage' => ['Nettoyage maison', 'Vitres & façades'],
            'Informatique & Réseau' => ['Dépannage PC', 'Câblage réseau'],
            'Electroménager' => ['Réparation LL/LV', 'Réfrigérateurs'],
            'Tôlerie & Soudure' => ['Soudure portes', 'Portails'],
            'Vitrier' => ['Remplacement vitre', 'Miroiterie'],
            'Toiture & Charpente' => ['Tuiles', 'Charpente'],
            'Antennes & Sat' => ['Installation parabole', 'Réglage'],
            'Désinsectisation & Dératisation' => ['Traitement insectes', 'Rongeurs'],
        ];

        // Création des catégories parentes + enfants
        foreach ($tree as $parentName => $children) {
            $parent = $this->createCategory($parentName, null, $manager);

            foreach ($children as $childName) {
                $this->createCategory($childName, $parent, $manager);
            }
        }

        $manager->flush();
    }

    private function createCategory(string $name, ?Category $parent, ObjectManager $manager): Category
    {
        $category = new Category();
        $category->setName($name);

        // Slug unique et stable
        $slug = strtolower($this->slugger->slug($name)->toString());
        $category->setSlug($slug);

        $category->setParent($parent);

        $manager->persist($category);

        // Référence pour lier les services dans ServiceDefinitionFixtures
        $this->addReference(self::REF_PREFIX.$slug, $category);

        return $category;
    }
}
