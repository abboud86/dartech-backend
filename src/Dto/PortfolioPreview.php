<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Représente une preview (≤ 4) d'URLs médias publiques.
 * Évolution future: remplacer par des objets avec métadonnées (caption, size, createdAt).
 */
final class PortfolioPreview
{
    /** @var list<string> */
    #[Groups(['public:artisan.read'])]
    public array $mediaUrls = [];

    /**
     * @param list<string> $mediaUrls
     */
    public function __construct(array $mediaUrls = [])
    {
        $this->mediaUrls = $mediaUrls;
    }

    public static function empty(): self
    {
        return new self([]);
    }
}
