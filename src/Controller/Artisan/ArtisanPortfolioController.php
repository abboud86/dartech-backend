<?php

namespace App\Controller\Artisan;

use App\Repository\ArtisanProfileRepository;
use App\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ArtisanPortfolioController extends AbstractController
{
    #[Route('/v1/artisans/{slug}/portfolio/preview', name: 'api_artisan_portfolio_preview', methods: ['GET'])]
    public function preview(
        string $slug,
        ArtisanProfileRepository $profiles,
        MediaRepository $mediaRepo,
    ): JsonResponse {
        // Résolution unifiée: slug (nouveau public id) OU alias legacy (User ULID)
        $profile = $profiles->findOneByPublicId($slug);
        if (!$profile) {
            return $this->json(['error' => 'Artisan not found'], 404);
        }

        // Comportement existant: médias publics uniquement, limit 4, ordre desc (géré côté repo)
        $media = $mediaRepo->findPortfolioPreview($profile, 4);
        $urls = array_map(static fn ($m) => $m->getPublicUrl(), $media);

        return $this->json($urls);
    }
}
