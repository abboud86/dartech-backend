<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

final class ArtisanPublicView
{
    #[Groups(['public:artisan.read'])]
    public string $slug;

    #[Groups(['public:artisan.read'])]
    public string $displayName;

    #[Groups(['public:artisan.read'])]
    public ?string $city = null;

    #[Groups(['public:artisan.read'])]
    public ?string $bio = null;

    #[Groups(['public:artisan.read'])]
    public ?string $avatarUrl = null;

    #[Groups(['public:artisan.read'])]
    public bool $verified = false;

    #[Groups(['public:artisan.read'])]
    public array $services = [];

    #[Groups(['public:artisan.read'])]
    public array $portfolioPreview = [];

    #[Groups(['public:artisan.read'])]
    public ?\DateTimeImmutable $updatedAt = null;

    public static function fromEntity(\App\Entity\ArtisanProfile $artisan): self
    {
        $dto = new self();

        // Base
        $dto->slug = (string) ($artisan->getUser()?->getId() ?? $artisan->getId()); // pas de slug sur ArtisanProfile => fallback id
        $dto->displayName = (string) $artisan->getDisplayName();
        $dto->city = $artisan->getCommune(); // champ public existant
        $dto->bio = $artisan->getBio();
        $dto->avatarUrl = null; // pas de champ avatar dans l'entité fournie

        // verified dérivé du KYC
        $dto->verified = \App\Enum\KycStatus::VERIFIED === $artisan->getKycStatus();

        // Horodatage
        $dto->updatedAt = $artisan->getUpdatedAt() ?? $artisan->getCreatedAt();

        // Services actifs (aperçu)
        $dto->services = [];
        foreach ($artisan->getArtisanServices() as $svc) {
            if (\App\Enum\ArtisanServiceStatus::ACTIVE !== $svc->getStatus()) {
                continue;
            }
            $dto->services[] = [
                'id' => (string) $svc->getId(),
                'name' => $svc->getTitle(),
                'slug' => $svc->getSlug(),
                'price' => $svc->getUnitAmount(),
            ];
        }

        // Portfolio: sera géré en P2-05.2
        $dto->portfolioPreview = [];

        return $dto;
    }
}
