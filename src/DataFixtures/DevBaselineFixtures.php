<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
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

        // 1) User
        $user = new User();
        $user->setEmail('artisan@example.test');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $om->persist($user);

        // 2) ArtisanProfile (KYC = verified)
        $ap = new ArtisanProfile();
        $ap->setUser($user);
        $ap->setDisplayName('Artisan Démo');
        $ap->setPhone('0600000000');
        $ap->setBio('Profil de démonstration');
        $ap->setWilaya('Alger');
        $ap->setCommune('Bab Ezzouar');
        // Robust against enum case name differences; uses backed value:
        $ap->setKycStatus(KycStatus::from('LA_VALEUR_VERIFIED'));     // ex: 'verified' ou 'VERIFIED'

        $om->persist($ap);

        // 3) ArtisanService (status = published)
        $svc = new ArtisanService();
        $svc->setArtisanProfile($ap);
        $svc->setStatus(ArtisanServiceStatus::from('LA_VALEUR_PUBLISHED')); // ex: 'published' ou 'PUBLISHED'
        // Uncomment if your entity has a name/title field:
        // $svc->setName('Service Démo');
        $svc->setCreatedAt($now);
        $om->persist($svc);

        $om->flush();
    }
}
