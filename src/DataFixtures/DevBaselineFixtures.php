<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\Category;
use App\Entity\ServiceDefinition;
use App\Entity\User;
use App\Enum\ArtisanServiceStatus;
use App\Enum\KycStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DevBaselineFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $om): void
    {
        $now = new \DateTimeImmutable();

        /** 1) User */
        $user = new User();
        $user->setEmail('artisan@example.test');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $om->persist($user);

        /** 2) ArtisanProfile (KYC = verified) */
        $ap = new ArtisanProfile();
        $ap->setUser($user);
        $ap->setDisplayName('Artisan Démo');
        $ap->setPhone('0600000000');
        $ap->setBio('Profil de démonstration');
        $ap->setWilaya('Alger');
        $ap->setCommune('Bab Ezzouar');
        $ap->setKycStatus(KycStatus::from('verified'));
        $om->persist($ap);

        /** 3) Category (obligatoire pour ServiceDefinition) */
        $cat = $om->getRepository(Category::class)->findOneBy([]);
        if (null === $cat) {
            $cat = new Category();
            $cat->setName('Général');
            $cat->setSlug('general');
            $om->persist($cat);
            $om->flush();
        }

        /** 4) ServiceDefinition (category NOT NULL + schema minimal) */
        $def = $om->getRepository(ServiceDefinition::class)->findOneBy([]);
        if (null === $def) {
            $def = new ServiceDefinition();
            $def->setCategory($cat);
            $def->setName('Service Démo');
            $def->setSlug('service-demo');
            $def->setAttributesSchema([]); // JSON minimal si requis
            $om->persist($def);
            $om->flush();
        }

        /** 5) ArtisanService (status = active) + liens + champs requis */
        $svc = new ArtisanService();
        $svc->setArtisanProfile($ap);
        $svc->setServiceDefinition($def);
        $svc->setTitle('Service Démo (baseline)');
        $svc->setSlug('service-demo-'.substr(md5((string) microtime(true)), 0, 6)); // unique
        $svc->setStatus(ArtisanServiceStatus::from('active')); // ✔ enum réel
        $svc->setUnitAmount(1000);        // ✔ NOT NULL
        $svc->setCurrency('DZD');         // ✔ NOT NULL
        $svc->setCreatedAt($now);
        $om->persist($svc);

        $om->flush();
    }
}
