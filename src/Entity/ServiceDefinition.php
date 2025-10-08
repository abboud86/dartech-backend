<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ServiceDefinitionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServiceDefinitionRepository::class)]
#[ORM\Table(name: 'service_definition')]
#[ORM\UniqueConstraint(name: 'uniq_service_definition_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_sd_category', columns: ['category_id'])]
#[ORM\Index(name: 'idx_sd_name', columns: ['name'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Slug must be unique.')]
class ServiceDefinition
{
    // ULID (doc Symfony UID + Doctrine type 'ulid')
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private ?Ulid $id = null;

    // Relation obligatoire vers Category (DB nullable=false + Assert\NotNull)
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Category $category = null;

    #[ORM\Column(length: 128)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private ?string $name = null;

    #[ORM\Column(length: 160, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 160)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Schéma d'attributs dynamiques (JSON natif DBAL)
    #[ORM\Column(type: 'json')]
    #[Assert\Type('array')]
    private array $attributesSchema = [];

    // Timestamps gérés côté PHP (pas de default SQL)
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ArtisanService>
     */
    #[ORM\OneToMany(targetEntity: ArtisanService::class, mappedBy: 'serviceDefinition')]
    private Collection $artisanServices;

    public function __construct()
    {
        $this->id = new Ulid();
        $this->createdAt = new \DateTimeImmutable();
        $this->artisanServices = new ArrayCollection();
    }

    // ——— Lifecycle ———

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!$this->slug && $this->name) {
            $this->slug = (string) (new AsciiSlugger())->slug($this->name)->lower();
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();

        if ($this->name && !$this->slug) {
            $this->slug = (string) (new AsciiSlugger())->slug($this->name)->lower();
        }
    }

    // ——— Getters / Setters ———

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    // Setter nullable pour compat statique; DB + validation empêchent null à la persistance
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /** Définit un slug normalisé (ASCII, lower), ou null si vide. */
    public function setSlug(?string $slug): self
    {
        $this->slug = $slug ? (string) (new AsciiSlugger())->slug($slug)->lower() : null;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Schéma JSON des attributs dynamiques (ex: types: string|enum|number|photo|bool).
     */
    public function getAttributesSchema(): array
    {
        return $this->attributesSchema;
    }

    public function setAttributesSchema(array $schema): self
    {
        $this->attributesSchema = $schema;

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
            $artisanService->setServiceDefinition($this);
        }

        return $this;
    }

    public function removeArtisanService(ArtisanService $artisanService): static
    {
        if ($this->artisanServices->removeElement($artisanService)) {
            // set the owning side to null (unless already changed)
            if ($artisanService->getServiceDefinition() === $this) {
                $artisanService->setServiceDefinition(null);
            }
        }

        return $this;
    }
}
