<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'IDX_audit_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'IDX_audit_target', columns: ['target_type', 'target_id'])]
#[ORM\Index(name: 'IDX_audit_actor', columns: ['actor_type', 'actor_id'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private ?Ulid $id = null;

    #[ORM\Column(length: 32)]
    private string $actorType;

    #[ORM\Column(length: 64)]
    private string $actorId;

    #[ORM\Column(length: 32)]
    private string $targetType;

    #[ORM\Column(length: 64)]
    private string $targetId;

    #[ORM\Column(length: 64)]
    private string $action;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $dataBefore = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $dataAfter = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersistSetId(): void
    {
        if (null === $this->id) {
            $this->id = new Ulid();
        }
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getActorType(): string
    {
        return $this->actorType;
    }

    public function setActorType(string $actorType): self
    {
        $this->actorType = $actorType;

        return $this;
    }

    public function getActorId(): string
    {
        return $this->actorId;
    }

    public function setActorId(string $actorId): self
    {
        $this->actorId = $actorId;

        return $this;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): self
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getTargetId(): string
    {
        return $this->targetId;
    }

    public function setTargetId(string $targetId): self
    {
        $this->targetId = $targetId;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getDataBefore(): ?array
    {
        return $this->dataBefore;
    }

    public function setDataBefore(?array $dataBefore): self
    {
        $this->dataBefore = $dataBefore;

        return $this;
    }

    public function getDataAfter(): ?array
    {
        return $this->dataAfter;
    }

    public function setDataAfter(?array $dataAfter): self
    {
        $this->dataAfter = $dataAfter;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
