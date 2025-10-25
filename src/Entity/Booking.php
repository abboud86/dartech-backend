<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\BookingStatus;
use App\Enum\CommunicationChannel;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'booking')]
#[ORM\HasLifecycleCallbacks]
class Booking
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $client = null;

    #[ORM\ManyToOne(targetEntity: ArtisanService::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?ArtisanService $artisanService = null;

    /**
     * ⚠️ Cette propriété est utilisée par le composant Workflow.
     * Elle doit être de type string pour le marking_store.
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $statusMarking = null;

    #[ORM\Column(type: 'string', length: 50, enumType: CommunicationChannel::class, nullable: true)]
    private ?CommunicationChannel $communicationChannel = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $estimatedAmount = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->id = new Ulid();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(User $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getArtisanService(): ?ArtisanService
    {
        return $this->artisanService;
    }

    public function setArtisanService(ArtisanService $artisanService): self
    {
        $this->artisanService = $artisanService;

        return $this;
    }

    /**
     * Retourne le statut sous forme d'enum métier.
     */
    public function getStatus(): ?BookingStatus
    {
        return $this->statusMarking ? BookingStatus::from($this->statusMarking) : null;
    }

    /**
     * Définit le statut métier (enum).
     */
    public function setStatus(?BookingStatus $status): self
    {
        $this->statusMarking = $status?->value;

        return $this;
    }

    public function getCommunicationChannel(): ?CommunicationChannel
    {
        return $this->communicationChannel;
    }

    public function setCommunicationChannel(?CommunicationChannel $channel): self
    {
        $this->communicationChannel = $channel;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getEstimatedAmount(): ?string
    {
        return $this->estimatedAmount;
    }

    public function setEstimatedAmount(?string $amount): self
    {
        $this->estimatedAmount = $amount;

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

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * 🧠 Méthodes utilisées par le composant Workflow
     * — le marquage doit être une string simple.
     */
    public function getStatusMarking(): ?string
    {
        return $this->statusMarking;
    }

    public function setStatusMarking(?string $marking): void
    {
        $this->statusMarking = $marking;
    }
}
