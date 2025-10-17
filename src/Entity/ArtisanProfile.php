<?php

namespace App\Entity;

use App\Enum\KycStatus;
use App\Exception\DomainException;
use App\Repository\ArtisanProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArtisanProfileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ArtisanProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: 'ap.display_name.not_blank', groups: ['create', 'update'])]
    #[Assert\Length(max: 80, maxMessage: 'ap.display_name.too_long', groups: ['create', 'update'])]
    private ?string $displayName = null;

    // phone : requis, format E.164, <=20
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'ap.phone.not_blank', groups: ['create', 'update'])]
    #[Assert\Regex(
        pattern: '/^\+[1-9]\d{7,14}$/',
        message: 'ap.phone.invalid',
        groups: ['create', 'update']
    )]
    #[Assert\Length(max: 20, maxMessage: 'ap.phone.too_long', groups: ['create', 'update'])]
    private ?string $phone = null;

    // bio : optionnelle, <=500
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'ap.bio.too_long', groups: ['create', 'update'])]
    private ?string $bio = null;

    // wilaya : requis, <=64
    #[ORM\Column(length: 64)]
    #[Assert\NotBlank(message: 'ap.wilaya.not_blank', groups: ['create', 'update'])]
    #[Assert\Length(max: 64, maxMessage: 'ap.wilaya.too_long', groups: ['create', 'update'])]
    private ?string $wilaya = null;

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank(message: 'ap.commune.not_blank', groups: ['create', 'update'])]
    #[Assert\Length(max: 64, maxMessage: 'ap.commune.too_long', groups: ['create', 'update'])]
    private ?string $commune = null;

    #[ORM\Column(enumType: KycStatus::class)]
    private KycStatus $kycStatus = KycStatus::PENDING;

    #[ORM\OneToOne(inversedBy: 'artisanProfile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * @var Collection<int, ArtisanService>
     */
    #[ORM\OneToMany(targetEntity: ArtisanService::class, mappedBy: 'artisanProfile')]
    private Collection $artisanServices;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'artisanProfile')]
    private Collection $media;

    public function __construct()
    {
        $this->kycStatus = KycStatus::PENDING;
        $this->artisanServices = new ArrayCollection();
        $this->media = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getWilaya(): ?string
    {
        return $this->wilaya;
    }

    public function setWilaya(string $wilaya): static
    {
        $this->wilaya = $wilaya;

        return $this;
    }

    public function getCommune(): ?string
    {
        return $this->commune;
    }

    public function setCommune(string $commune): static
    {
        $this->commune = $commune;

        return $this;
    }

    public function getKycStatus(): KycStatus
    {
        return $this->kycStatus;
    }

    public function setKycStatus(KycStatus $kycStatus): static
    {
        $this->kycStatus = $kycStatus;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function changeKycStatus(KycStatus $to): void
    {
        $from = $this->kycStatus;

        // Valid transitions:
        // pending -> verified | rejected
        if (KycStatus::PENDING === $from && (KycStatus::VERIFIED === $to || KycStatus::REJECTED === $to)) {
            $this->kycStatus = $to;

            return;
        }

        // rejected -> pending (resubmission)
        if (KycStatus::REJECTED === $from && KycStatus::PENDING === $to) {
            $this->kycStatus = $to;

            return;
        }

        // Otherwise: forbidden transition
        throw new DomainException(sprintf('Transition KYC interdite: %s → %s', $from->value, $to->value));
    }

    /**
     * @return Collection<int, ArtisanService>
     */
    public function getArtisanServices(): Collection
    {
        return $this->artisanServices;
    }

    public function addArtisanService(ArtisanService $artisanService): static
    {
        if (!$this->artisanServices->contains($artisanService)) {
            $this->artisanServices->add($artisanService);
            $artisanService->setArtisanProfile($this);
        }

        return $this;
    }

    public function removeArtisanService(ArtisanService $artisanService): static
    {
        if ($this->artisanServices->removeElement($artisanService)) {
            // set the owning side to null (unless already changed)
            if ($artisanService->getArtisanProfile() === $this) {
                $artisanService->setArtisanProfile(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedium(Media $medium): static
    {
        if (!$this->media->contains($medium)) {
            $this->media->add($medium);
            $medium->setArtisanProfile($this);
        }

        return $this;
    }

    public function removeMedium(Media $medium): static
    {
        if ($this->media->removeElement($medium)) {
            // set the owning side to null (unless already changed)
            if ($medium->getArtisanProfile() === $this) {
                $medium->setArtisanProfile(null);
            }
        }

        return $this;
    }
}
