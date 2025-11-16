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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FactoryContractTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private TestEntityFactory $factory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->factory = new TestEntityFactory($this->em);
    }

    public function testCreateUserIsPersistable(): void
    {
        $user = $this->factory->createUser('factory-user@example.test');

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->getId());
        $this->assertNotNull($user->getEmail());

        $reloaded = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertNotNull($reloaded);
    }

    public function testCreateArtisanProfileIsPersistable(): void
    {
        $profile = $this->factory->createArtisanProfile();

        $this->assertInstanceOf(ArtisanProfile::class, $profile);
        $this->assertNotNull($profile->getId());
        $this->assertNotNull($profile->getUser());
        $this->assertNotNull($profile->getDisplayName());
        $this->assertNotNull($profile->getPhone());
        $this->assertNotNull($profile->getWilaya());
        $this->assertNotNull($profile->getCommune());
        $this->assertNotNull($profile->getCreatedAt());

        $reloaded = $this->em->getRepository(ArtisanProfile::class)->find($profile->getId());
        $this->assertNotNull($reloaded);
    }

    public function testCreateArtisanServiceIsPersistable(): void
    {
        $profile = $this->factory->createArtisanProfile();
        $service = $this->factory->createArtisanService($profile);

        $this->assertInstanceOf(ArtisanService::class, $service);
        $this->assertNotNull($service->getId());
        $this->assertSame($profile, $service->getArtisanProfile());

        $this->assertInstanceOf(ServiceDefinition::class, $service->getServiceDefinition());
        $this->assertNotNull($service->getTitle());
        $this->assertNotNull($service->getSlug());
        $this->assertSame(ArtisanServiceStatus::DRAFT, $service->getStatus());

        $this->assertNotNull($service->getCreatedAt());
        $this->assertNotNull($service->getUpdatedAt());

        $reloaded = $this->em->getRepository(ArtisanService::class)->find($service->getId());
        $this->assertNotNull($reloaded);
    }

    public function testCreateBookingIsPersistable(): void
    {
        $client = $this->factory->createUser();
        $service = $this->factory->createArtisanService();
        $booking = $this->factory->createBooking($client, $service);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertNotNull($booking->getId());
        $this->assertSame($client, $booking->getClient());
        $this->assertSame($service, $booking->getArtisanService());

        $this->assertSame(BookingStatus::INQUIRY, $booking->getStatus());
        $this->assertSame(CommunicationChannel::WHATSAPP, $booking->getCommunicationChannel());

        $this->assertNotNull($booking->getCreatedAt());

        $reloaded = $this->em->getRepository(Booking::class)->find($booking->getId());
        $this->assertNotNull($reloaded);
    }
}
