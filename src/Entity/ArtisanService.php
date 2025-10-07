<?php

namespace App\Entity;

use App\Enum\ArtisanServiceStatus;
use App\Repository\ArtisanServiceRepository;
use App\Validator\SingleActivePublication;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'artisan_service')]
#[ORM\UniqueConstraint(name: 'UNIQ_artisan_slug', columns: ['artisan_profile_id', 'slug'])]
#[ORM\Index(name: 'IDX_artisan_profile', columns: ['artisan_profile_id'])]
#[ORM\Index(name: 'IDX_service_definition', columns: ['service_definition_id'])]
#[ORM\Index(name: 'IDX_status', columns: ['status'])]
#[UniqueEntity(fields: ['artisanProfile', 'slug'], message: 'Slug must be unique per artisan')]
#[SingleActivePublication(message: 'Only one active service per artisan & service definition.')]
#[ORM\Entity(repositoryClass: ArtisanServiceRepository::class)]
class ArtisanService
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(inversedBy: 'artisanServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\ManyToOne(inversedBy: 'artisanServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ServiceDefinition $serviceDefinition = null;

    #[ORM\Column(length: 160)]
    private ?string $title = null;

    #[ORM\Column(length: 180)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $unitAmount = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column(enumType: ArtisanServiceStatus::class)]
    #[Assert\NotNull]
    private ArtisanServiceStatus $status = ArtisanServiceStatus::DRAFT;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->id = new Ulid();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getArtisanProfile(): ?ArtisanProfile
    {
        return $this->artisanProfile;
    }

    public function setArtisanProfile(?ArtisanProfile $artisanProfile): static
    {
        $this->artisanProfile = $artisanProfile;

        return $this;
    }

    public function getServiceDefinition(): ?ServiceDefinition
    {
        return $this->serviceDefinition;
    }

    public function setServiceDefinition(?ServiceDefinition $serviceDefinition): static
    {
        $this->serviceDefinition = $serviceDefinition;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUnitAmount(): ?int
    {
        return $this->unitAmount;
    }

    public function setUnitAmount(int $unitAmount): static
    {
        $this->unitAmount = $unitAmount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getStatus(): ArtisanServiceStatus
    {
        return $this->status;
    }

    public function setStatus(ArtisanServiceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[Callback('validatePublishedAt')]
    public function validatePublishedAt(ExecutionContextInterface $context): void
    {
        if (ArtisanServiceStatus::ACTIVE === $this->status && null === $this->publishedAt) {
            $context->buildViolation('publishedAt is required when status is active')
                ->atPath('publishedAt')
                ->addViolation();
        }
    }
}
