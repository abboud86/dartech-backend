<?php

namespace App\Tests\Validator;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\ServiceDefinition;
use App\Enum\ArtisanServiceStatus;
use App\Repository\ArtisanServiceRepository;
use App\Validator\SingleActivePublication;
use App\Validator\SingleActivePublicationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class SingleActivePublicationValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ArtisanServiceRepository&MockObject */
    private ArtisanServiceRepository $repo;

    protected function createValidator(): SingleActivePublicationValidator
    {
        /* @var ArtisanServiceRepository&MockObject $repo */
        $this->repo = $this->createMock(ArtisanServiceRepository::class);

        return new SingleActivePublicationValidator($this->repo);
    }

    private function makeEntity(ArtisanServiceStatus $status, ?Ulid $id = null): ArtisanService
    {
        $ap = new ArtisanProfile();
        $def = new ServiceDefinition();

        $s = new ArtisanService();
        if (method_exists($s, 'setArtisanProfile')) {
            $s->setArtisanProfile($ap);
        }
        if (method_exists($s, 'setServiceDefinition')) {
            $s->setServiceDefinition($def);
        }
        if (method_exists($s, 'setStatus')) {
            $s->setStatus($status);
        }

        // If your getId() returns a ULID only after persistence,
        // we emulate it here by adding a dynamic getter through a stub.
        if (null !== $id) {
            // Build a proxy to return a fixed id without touching the real entity code
            $proxy = new class($s, $id) extends ArtisanService {
                public function __construct(private ArtisanService $inner, private Ulid $id)
                {
                }

                public function __call($name, $args)
                {
                    return $this->inner->{$name}(...$args);
                }

                public function getId(): ?Ulid
                {
                    return $this->id;
                }
            };
            // Copy configured props already set on $s
            $proxy->setArtisanProfile($ap);
            $proxy->setServiceDefinition($def);
            $proxy->setStatus($status);

            return $proxy;
        }

        return $s;
    }

    public function testAllowsActiveWhenNoneExists(): void
    {
        $entity = $this->makeEntity(ArtisanServiceStatus::ACTIVE, null);

        $this->repo
            ->expects($this->once())
            ->method('hasActiveForCouple')
            ->with(
                $this->isInstanceOf(ArtisanProfile::class),
                $this->isInstanceOf(ServiceDefinition::class),
                $this->isNull(), // excludeId must be NULL for a new entity
            )
            ->willReturn(false);

        $this->validator->validate($entity, new SingleActivePublication());

        $this->assertNoViolation();
    }

    public function testViolatesWhenAlreadyActiveForCouple(): void
    {
        $entity = $this->makeEntity(ArtisanServiceStatus::ACTIVE, null);

        $this->repo
            ->expects($this->once())
            ->method('hasActiveForCouple')
            ->with(
                $this->isInstanceOf(ArtisanProfile::class),
                $this->isInstanceOf(ServiceDefinition::class),
                $this->isNull(),
            )
            ->willReturn(true);

        $this->validator->validate($entity, new SingleActivePublication());

        $this->buildViolation('Only one ACTIVE offer is allowed per artisan and service definition.')
            ->atPath('property.path.status')
            ->assertRaised();
    }

    public function testExcludesIdWhenEntityIsManaged(): void
    {
        $id = new Ulid();
        $entity = $this->makeEntity(ArtisanServiceStatus::ACTIVE, $id);

        $this->repo
            ->expects($this->once())
            ->method('hasActiveForCouple')
            ->with(
                $this->isInstanceOf(ArtisanProfile::class),
                $this->isInstanceOf(ServiceDefinition::class),
                $this->equalTo($id), // exclude current id when editing
            )
            ->willReturn(false);

        $this->validator->validate($entity, new SingleActivePublication());

        $this->assertNoViolation();
    }

    public function testNoCheckWhenNotActive(): void
    {
        $entity = $this->makeEntity(ArtisanServiceStatus::DRAFT, null);

        $this->repo
            ->expects($this->never())
            ->method('hasActiveForCouple');

        $this->validator->validate($entity, new SingleActivePublication());
        $this->assertNoViolation();
    }
}
