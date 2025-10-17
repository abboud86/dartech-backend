<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Dto\ArtisanPublicView;
use App\Repository\ArtisanProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/v1/artisans', name: 'public_artisan_')]
final class ArtisanPublicController extends AbstractController
{
    public function __construct(
        private readonly ArtisanProfileRepository $artisanRepo,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        // {slug} = User.id (ULID) provisoire
        $artisan = $this->artisanRepo->findOnePublicByPublicId($slug);

        if (!$artisan) {
            return $this->json(['error' => 'artisan_not_found'], 404);
        }

        $dto = ArtisanPublicView::fromEntity($artisan);

        // ✅ Ajout portfolioPreview (≤ 4 URLs)
        $previewUrls = $this->artisanRepo->findPortfolioPreview($artisan, 4);
        $dto->portfolioPreview = $previewUrls;

        $json = $this->serializer->serialize(
            $dto,
            'json',
            ['groups' => ['public:artisan.read']]
        );

        return new JsonResponse(
            $json,
            200,
            ['Cache-Control' => 'public, max-age=300, s-maxage=600'],
            true
        );
    }
}
