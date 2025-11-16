<?php

declare(strict_types=1);

namespace App\Tests\Functional\Support;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\Booking;
use App\Entity\ServiceDefinition;
use App\Entity\User;
use App\Enum\ArtisanServiceStatus;
use App\Enum\BookingStatus;
use App\Enum\CommunicationChannel;
use Doctrine\ORM\EntityManagerInterface;

final class TestEntityFactory
{
    private static int $sequence = 1;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    // -------------------------------------------------------------------------
    // User
    // -------------------------------------------------------------------------

    /**
     * Crée et persiste un User avec un email unique.
     *
     * @param string|null $email Email de base (si null, un email de test est généré)
     */
    public function createUser(?string $email = null): User
    {
        $n = self::$sequence++;

        if (null === $email) {
            $email = sprintf('user%d@example.test', $n);
        }

        $uniqueEmail = $this->makeUniqueEmail($email);

        $user = new User();
        $user->setEmail($uniqueEmail);
        // On ne s'authentifie jamais avec le mot de passe en tests fonctionnels,
        // il faut juste une valeur non nulle.
        $user->setPassword('test-password-hash');

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Garantit l’unicité de l’email côté base, même si plusieurs tests
     * utilisent la même valeur de base.
     */
    private function makeUniqueEmail(string $baseEmail): string
    {
        $repo = $this->em->getRepository(User::class);

        // Si l’email n’existe pas encore, on le garde tel quel.
        if (null === $repo->findOneBy(['email' => $baseEmail])) {
            return $baseEmail;
        }

        // Sinon, on ajoute un suffixe +N avant le @ tant qu’il existe déjà.
        [$local, $domain] = explode('@', $baseEmail, 2);
        $i = 1;

        while (true) {
            $candidate = sprintf('%s+%d@%s', $local, $i, $domain);
            if (null === $repo->findOneBy(['email' => $candidate])) {
                return $candidate;
            }
            ++$i;
        }
    }

    // -------------------------------------------------------------------------
    // ArtisanProfile
    // -------------------------------------------------------------------------

    /**
     * Crée et persiste un ArtisanProfile complet.
     *
     * @param User|null $owner     User propriétaire ; créé si null
     * @param array     $overrides displayName, phone, bio, wilaya, commune, kycStatus
     */
    public function createArtisanProfile(?User $owner = null, array $overrides = []): ArtisanProfile
    {
        if (null === $owner) {
            $owner = $this->createUser();
        }

        $profile = new ArtisanProfile();
        $profile->setUser($owner);

        $displayName = $overrides['displayName'] ?? 'Artisan Test';
        $phone = $overrides['phone'] ?? '+213500000000';
        $bio = $overrides['bio'] ?? 'Bio de test';
        $wilaya = $overrides['wilaya'] ?? 'Alger';
        $commune = $overrides['commune'] ?? 'Alger-Centre';

        $profile->setDisplayName($displayName);
        $profile->setPhone($phone);
        $profile->setBio($bio);
        $profile->setWilaya($wilaya);
        $profile->setCommune($commune);

        if (isset($overrides['kycStatus'])) {
            $profile->setKycStatus($overrides['kycStatus']);
        }

        $this->em->persist($profile);
        $this->em->flush();

        return $profile;
    }

    // -------------------------------------------------------------------------
    // ArtisanService
    // -------------------------------------------------------------------------

    /**
     * Crée et persiste un ArtisanService minimalement valide (status=DRAFT).
     *
     * @param ArtisanProfile|null $profile   Profil artisan ; créé si null
     * @param array               $overrides title, slug, description, unitAmount,
     *                                       currency, status, publishedAt, serviceDefinition
     */
    public function createArtisanService(
        ?ArtisanProfile $profile = null,
        array $overrides = [],
    ): ArtisanService {
        if (null === $profile) {
            $profile = $this->createArtisanProfile();
        }

        $serviceDefinition = $overrides['serviceDefinition'] ?? $this->getAnyServiceDefinition();
        if (null === $serviceDefinition) {
            throw new \RuntimeException('No ServiceDefinition found. Make sure fixtures are loaded.');
        }

        $service = new ArtisanService();
        $service->setArtisanProfile($profile);
        $service->setServiceDefinition($serviceDefinition);

        $title = $overrides['title'] ?? 'Service de test';
        $slug = $overrides['slug'] ?? $this->generateServiceSlug($profile);
        $desc = $overrides['description'] ?? 'Description de test';

        $unitAmount = $overrides['unitAmount'] ?? 1000;
        $currency = $overrides['currency'] ?? 'DZD';

        $status = $overrides['status'] ?? ArtisanServiceStatus::DRAFT;

        $service->setTitle($title);
        $service->setSlug($slug);
        $service->setDescription($desc);
        $service->setUnitAmount($unitAmount);
        $service->setCurrency($currency);
        $service->setStatus($status);

        if (isset($overrides['publishedAt'])) {
            /** @var \DateTimeImmutable $publishedAt */
            $publishedAt = $overrides['publishedAt'];
            $service->setPublishedAt($publishedAt);
        }

        $this->em->persist($service);
        $this->em->flush();

        return $service;
    }

    private function getAnyServiceDefinition(): ?ServiceDefinition
    {
        /** @var ServiceDefinition|null $sd */
        $sd = $this->em->getRepository(ServiceDefinition::class)->findOneBy([]);

        return $sd;
    }

    private function generateServiceSlug(ArtisanProfile $profile): string
    {
        $n = self::$sequence++;

        // Un slug simple mais unique par artisan
        $base = $profile->getDisplayName() ?? 'artisan';
        $base = strtolower(trim(preg_replace('~[^a-zA-Z0-9]+~', '-', $base) ?? 'artisan', '-'));

        if ('' === $base) {
            $base = 'artisan';
        }

        return sprintf('%s-service-%d', $base, $n);
    }

    // -------------------------------------------------------------------------
    // Booking
    // -------------------------------------------------------------------------

    /**
     * Crée et persiste un Booking complet, relié à un client et un service.
     *
     * @param User|null           $client  User client ; créé si null
     * @param ArtisanService|null $service Service ; créé si null (et donc profile + SD)
     * @param array               $overrides status, communicationChannel,
     *                                       scheduledAt, estimatedAmount
     */
    public function createBooking(
        ?User $client = null,
        ?ArtisanService $service = null,
        array $overrides = [],
    ): Booking {
        if (null === $client) {
            $client = $this->createUser();
        }

        if (null === $service) {
            $service = $this->createArtisanService();
        }

        $booking = new Booking();
        $booking->setClient($client);
        $booking->setArtisanService($service);

        $status = $overrides['status'] ?? BookingStatus::INQUIRY;
        $booking->setStatus($status);

        $channel = $overrides['communicationChannel'] ?? CommunicationChannel::WHATSAPP;
        $booking->setCommunicationChannel($channel);

        if (\array_key_exists('scheduledAt', $overrides)) {
            /** @var \DateTimeImmutable|null $scheduledAt */
            $scheduledAt = $overrides['scheduledAt'];
            $booking->setScheduledAt($scheduledAt);
        }

        if (\array_key_exists('estimatedAmount', $overrides)) {
            /** @var string|null $amount */
            $amount = $overrides['estimatedAmount'];
            $booking->setEstimatedAmount($amount);
        }

        $this->em->persist($booking);
        $this->em->flush();

        return $booking;
    }
}
