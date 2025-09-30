<?php

namespace App\Entity;

use App\Enum\KycStatus;
use App\Repository\ArtisanProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtisanProfileRepository::class)]
class ArtisanProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private ?string $displayName = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 64)]
    private ?string $wilaya = null;

    #[ORM\Column(length: 64)]
    private ?string $commune = null;

    #[ORM\Column(enumType: KycStatus::class)]
    private KycStatus $kycStatus = KycStatus::PENDING;

    #[ORM\OneToOne(inversedBy: 'artisanProfile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

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
}
