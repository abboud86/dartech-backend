<?php

namespace App\Tests\Kernel;

use App\Entity\ArtisanProfile;
use App\Entity\User;
use App\Enum\KycStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // +++

class ArtisanProfilePersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    public function testPersistArtisanProfileWithUser(): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class); // +++
        $email = 'artisan+'.\bin2hex(\random_bytes(4)).'@example.test';
        $user = (new User())
            ->setEmail($email)
            ->setPassword($hasher->hashPassword(new User(), 'Password123!')); // +++ hash

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Menuisier Ahmed')
            ->setPhone('0550123456')
            ->setBio('Menuiserie sur Alger')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar')
            ->setKycStatus(KycStatus::PENDING);

        $this->em->persist($user);
        $this->em->persist($ap);
        $this->em->flush();

        $this->assertNotNull($ap->getId());
        $this->assertNotNull($ap->getUser()?->getId());
    }
}
