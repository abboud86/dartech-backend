<?php

namespace App\Controller\Api;

use App\Repository\ArtisanProfileRepository;
use App\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ArtisanPortfolioController extends AbstractController
{
    #[Route('/v1/artisans/{id<\d+>}/portfolio/preview', name: 'api_artisan_portfolio_preview', methods: ['GET'])]
    public function preview(
        int $id,
        ArtisanProfileRepository $profiles,
        MediaRepository $mediaRepo,
    ): JsonResponse {
        $profile = $profiles->find($id);
        if (!$profile) {
            return $this->json(['error' => 'Artisan not found'], 404);
        }

        $media = $mediaRepo->findPortfolioPreview($profile, 4);
        $urls = array_map(static fn ($m) => $m->getPublicUrl(), $media);

        return $this->json($urls);
    }
}
