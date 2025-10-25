<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'booking_timeline')]
#[ORM\HasLifecycleCallbacks]
class BookingTimeline
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: Booking::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Booking $booking = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    // ex: INQUIRY, CONTACTED, SCHEDULED, DONE, CANCELED
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $fromStatus = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $toStatus;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    public function __construct(
        Booking $booking,
        string $toStatus,
        ?string $fromStatus = null,
        ?User $actor = null,
        ?array $context = null,
        ?\DateTimeImmutable $occurredAt = null,
    ) {
        $this->id = new Ulid();
        $this->booking = $booking;
        $this->toStatus = $toStatus;
        $this->fromStatus = $fromStatus;
        $this->actor = $actor;
        $this->context = $context;
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function getFromStatus(): ?string
    {
        return $this->fromStatus;
    }

    public function getToStatus(): string
    {
        return $this->toStatus;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }
}
