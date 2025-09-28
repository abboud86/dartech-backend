<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\ServiceDefinition;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ServiceDefinitionFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public static function getGroups(): array
    {
        return ['catalogue'];
    }

    public function getDependencies(): array
    {
        // Garantit que CategoryFixtures est chargée avant.
        return [CategoryFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        /**
         * ≥ 60 services répartis par catégories parentes.
         * On recalcule les slugs avec le même Slugger que CategoryFixtures.
         */
        $map = [
            'Plomberie' => [
                ['name' => 'Réparation fuite', 'attrs' => $this->schemaSimple()],
                ['name' => 'Débouchage évier', 'attrs' => $this->schemaSimple()],
                ['name' => 'Pose robinetterie', 'attrs' => $this->schemaMatSpec()],
            ],
            'Électricité' => [
                ['name' => 'Dépannage prise/circuit', 'attrs' => $this->schemaSimple()],
                ['name' => 'Installation luminaire', 'attrs' => $this->schemaElec()],
                ['name' => 'Mise aux normes tableau', 'attrs' => $this->schemaElec()],
            ],
            'Climatisation & Froid' => [
                ['name' => 'Pose climatiseur split', 'attrs' => $this->schemaClim()],
                ['name' => 'Entretien/Recharge', 'attrs' => $this->schemaClim()],
                ['name' => 'Dépannage clim', 'attrs' => $this->schemaSimple()],
            ],
            'Maçonnerie' => [
                ['name' => 'Ouverture cloison', 'attrs' => $this->schemaMatSpec()],
                ['name' => 'Dalle béton petite surface', 'attrs' => $this->schemaMatSpec()],
                ['name' => 'Réparations fissures', 'attrs' => $this->schemaSimple()],
            ],
            'Peinture & Décoration' => [
                ['name' => 'Peinture intérieure m²', 'attrs' => $this->schemaPeinture()],
                ['name' => 'Peinture façade', 'attrs' => $this->schemaPeinture()],
                ['name' => 'Enduit & préparation', 'attrs' => $this->schemaSimple()],
            ],
            'Menuiserie Bois' => [
                ['name' => 'Pose porte intérieure', 'attrs' => $this->schemaMatSpec()],
                ['name' => 'Fabrication dressing', 'attrs' => $this->schemaMatSpec()],
                ['name' => 'Réparation meuble', 'attrs' => $this->schemaSimple()],
            ],
            'Menuiserie Aluminium & PVC' => [
                ['name' => 'Fenêtre alu/PVC', 'attrs' => $this->schemaMatSpec()],
                ['name' => 'Baie coulissante', 'attrs' => $this->schemaMatSpec()],
                ['name' => 'Moustiquaire', 'attrs' => $this->schemaSimple()],
            ],
            'Serrurerie' => [
                ['name' => 'Ouverture de porte', 'attrs' => $this->schemaUrgence()],
                ['name' => 'Remplacement cylindre', 'attrs' => $this->schemaSimple()],
                ['name' => 'Porte blindée', 'attrs' => $this->schemaMatSpec()],
            ],
            'Carrelage & Sols' => [
                ['name' => 'Pose carrelage m²', 'attrs' => $this->schemaCarrelage()],
                ['name' => 'Ragréage', 'attrs' => $this->schemaSimple()],
                ['name' => 'Pose plinthes', 'attrs' => $this->schemaSimple()],
            ],
            'Plâtre & Faux plafonds' => [
                ['name' => 'Cloison BA13', 'attrs' => $this->schemaPlatre()],
                ['name' => 'Faux plafond', 'attrs' => $this->schemaPlatre()],
                ['name' => 'Correction défauts', 'attrs' => $this->schemaSimple()],
            ],
            'Étanchéité & Isolation' => [
                ['name' => 'Étanchéité toiture', 'attrs' => $this->schemaEtanche()],
                ['name' => 'Isolation thermique', 'attrs' => $this->schemaEtanche()],
                ['name' => 'Traitement infiltrations', 'attrs' => $this->schemaSimple()],
            ],
            'Jardinage & Espaces verts' => [
                ['name' => 'Tonte pelouse', 'attrs' => $this->schemaJardin()],
                ['name' => 'Taille haies', 'attrs' => $this->schemaJardin()],
                ['name' => 'Système arrosage', 'attrs' => $this->schemaSimple()],
            ],
            'Nettoyage' => [
                ['name' => 'Nettoyage maison', 'attrs' => $this->schemaNettoyage()],
                ['name' => 'Vitres & façades', 'attrs' => $this->schemaNettoyage()],
                ['name' => 'Après travaux', 'attrs' => $this->schemaNettoyage()],
            ],
            'Informatique & Réseau' => [
                ['name' => 'Dépannage PC', 'attrs' => $this->schemaIT()],
                ['name' => 'Installation Wi-Fi', 'attrs' => $this->schemaIT()],
                ['name' => 'Câblage RJ45', 'attrs' => $this->schemaIT()],
            ],
            'Electroménager' => [
                ['name' => 'Réparation lave-linge', 'attrs' => $this->schemaSimple()],
                ['name' => 'Réfrigérateur', 'attrs' => $this->schemaSimple()],
                ['name' => 'Lave-vaisselle', 'attrs' => $this->schemaSimple()],
            ],
            'Tôlerie & Soudure' => [
                ['name' => 'Portail métallique', 'attrs' => $this->schemaMatSpec()],
                ['name' => 'Soudures diverses', 'attrs' => $this->schemaSimple()],
                ['name' => 'Garde-corps', 'attrs' => $this->schemaSimple()],
            ],
            'Vitrier' => [
                ['name' => 'Remplacement vitre', 'attrs' => $this->schemaSimple()],
                ['name' => 'Miroiterie sur mesure', 'attrs' => $this->schemaSimple()],
                ['name' => 'Double vitrage', 'attrs' => $this->schemaSimple()],
            ],
            'Toiture & Charpente' => [
                ['name' => 'Réfection toiture', 'attrs' => $this->schemaEtanche()],
                ['name' => 'Charpente bois', 'attrs' => $this->schemaMatSpec()],
                ['name' => 'Gouttières', 'attrs' => $this->schemaSimple()],
            ],
            'Antennes & Sat' => [
                ['name' => 'Installation parabole', 'attrs' => $this->schemaSimple()],
                ['name' => 'Réglage antenne', 'attrs' => $this->schemaSimple()],
                ['name' => 'Câblage TV', 'attrs' => $this->schemaSimple()],
            ],
            'Désinsectisation & Dératisation' => [
                ['name' => 'Traitement insectes', 'attrs' => $this->schemaHSE()],
                ['name' => 'Traitement rongeurs', 'attrs' => $this->schemaHSE()],
                ['name' => 'Désinfection', 'attrs' => $this->schemaHSE()],
            ],
        ];

        $total = 0;
        foreach ($map as $categoryName => $services) {
            $catSlug = strtolower($this->slugger->slug($categoryName)->toString());
            /** @var Category $category */
            $category = $this->getReference(CategoryFixtures::REF_PREFIX.$catSlug, Category::class);

            foreach ($services as $svc) {
                $entity = new ServiceDefinition();
                $entity->setCategory($category);

                $name = $svc['name'];
                $entity->setName($name);

                $slug = strtolower($this->slugger->slug($name)->toString());
                $entity->setSlug($slug);

                $entity->setAttributesSchema($svc['attrs']); // champ json/jsonb

                $manager->persist($entity);
                ++$total;
            }
        }

        // Si jamais < 60, compléter sur une catégorie de repli (ex. 'plomberie')
        while ($total < 60) {
            /** @var Category $fallbackCat */
            $fallbackCat = $this->getReference(CategoryFixtures::REF_PREFIX.'plomberie', Category::class);

            $name = 'Service générique '.($total + 1);
            $entity = (new ServiceDefinition())
                ->setCategory($fallbackCat)
                ->setName($name)
                ->setSlug(strtolower($this->slugger->slug($name)->toString()))
                ->setAttributesSchema($this->schemaSimple());

            $manager->persist($entity);
            ++$total;
        }

        $manager->flush();
    }

    /**
     * Schémas d’attributs (JSON) : {string|enum|number|bool|photo}, required: bool.
     */
    private function schemaSimple(): array
    {
        return [
            'fields' => [
                ['key' => 'description', 'type' => 'string', 'required' => true],
                ['key' => 'photos', 'type' => 'photo', 'required' => false],
            ],
        ];
    }

    private function schemaMatSpec(): array
    {
        return [
            'fields' => [
                ['key' => 'surface_m2', 'type' => 'number', 'required' => true],
                ['key' => 'materiau', 'type' => 'enum', 'required' => true, 'options' => ['bois', 'alu', 'pvc', 'acier', 'beton']],
                ['key' => 'photos', 'type' => 'photo', 'required' => false],
            ],
        ];
    }

    private function schemaElec(): array
    {
        return [
            'fields' => [
                ['key' => 'puissance_w', 'type' => 'number', 'required' => false],
                ['key' => 'norme', 'type' => 'enum', 'required' => true, 'options' => ['CENELEC', 'IEC']],
                ['key' => 'description', 'type' => 'string', 'required' => false],
            ],
        ];
    }

    private function schemaClim(): array
    {
        return [
            'fields' => [
                ['key' => 'type_fluid', 'type' => 'enum', 'required' => false, 'options' => ['R410a', 'R32', 'Autre']],
                ['key' => 'unite_btu', 'type' => 'number', 'required' => false],
                ['key' => 'hauteur_pose_m', 'type' => 'number', 'required' => false],
            ],
        ];
    }

    private function schemaPeinture(): array
    {
        return [
            'fields' => [
                ['key' => 'surface_m2', 'type' => 'number', 'required' => true],
                ['key' => 'type_peinture', 'type' => 'enum', 'required' => true, 'options' => ['acrylique', 'glycero', 'siloxane']],
                ['key' => 'couleur', 'type' => 'string', 'required' => false],
            ],
        ];
    }

    private function schemaCarrelage(): array
    {
        return [
            'fields' => [
                ['key' => 'surface_m2', 'type' => 'number', 'required' => true],
                ['key' => 'format', 'type' => 'enum', 'required' => false, 'options' => ['30x30', '60x60', '90x90']],
                ['key' => 'pose_sans_joint', 'type' => 'bool', 'required' => false],
            ],
        ];
    }

    private function schemaPlatre(): array
    {
        return [
            'fields' => [
                ['key' => 'surface_m2', 'type' => 'number', 'required' => true],
                ['key' => 'type', 'type' => 'enum', 'required' => true, 'options' => ['BA13', 'Hydrofuge', 'Feu']],
                ['key' => 'isolation', 'type' => 'bool', 'required' => false],
            ],
        ];
    }

    private function schemaEtanche(): array
    {
        return [
            'fields' => [
                ['key' => 'surface_m2', 'type' => 'number', 'required' => true],
                ['key' => 'produit', 'type' => 'enum', 'required' => true, 'options' => ['membrane', 'resine', 'bitume']],
                ['key' => 'pente_toit_pct', 'type' => 'number', 'required' => false],
            ],
        ];
    }

    private function schemaJardin(): array
    {
        return [
            'fields' => [
                ['key' => 'surface_m2', 'type' => 'number', 'required' => false],
                ['key' => 'dechets_a_ev', 'type' => 'bool', 'required' => false],
                ['key' => 'frequence', 'type' => 'enum', 'required' => false, 'options' => ['ponctuel', 'hebdo', 'mensuel']],
            ],
        ];
    }

    private function schemaNettoyage(): array
    {
        return [
            'fields' => [
                ['key' => 'surface_m2', 'type' => 'number', 'required' => false],
                ['key' => 'type_lieux', 'type' => 'enum', 'required' => true, 'options' => ['domicile', 'bureau', 'indus']],
                ['key' => 'produits_fournis', 'type' => 'bool', 'required' => false],
            ],
        ];
    }

    private function schemaIT(): array
    {
        return [
            'fields' => [
                ['key' => 'os', 'type' => 'enum', 'required' => false, 'options' => ['Windows', 'macOS', 'Linux']],
                ['key' => 'urgence', 'type' => 'enum', 'required' => false, 'options' => ['basse', 'normale', 'haute']],
                ['key' => 'description', 'type' => 'string', 'required' => false],
            ],
        ];
    }

    private function schemaHSE(): array
    {
        return [
            'fields' => [
                ['key' => 'type_traitement', 'type' => 'enum', 'required' => true, 'options' => ['insectes', 'rongeurs', 'desinfection']],
                ['key' => 'surface_m2', 'type' => 'number', 'required' => false],
                ['key' => 'acces_toiture', 'type' => 'bool', 'required' => false],
            ],
        ];
    }

    private function schemaUrgence(): array
    {
        return [
            'fields' => [
                ['key' => 'urgence', 'type' => 'enum', 'required' => true, 'options' => ['immédiate', 'sous_24h', 'sous_48h']],
                ['key' => 'description', 'type' => 'string', 'required' => false],
                ['key' => 'photos', 'type' => 'photo', 'required' => false],
            ],
        ];
    }
}
