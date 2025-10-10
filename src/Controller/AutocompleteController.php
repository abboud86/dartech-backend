<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ArtisanServiceStatus;
use App\Enum\KycStatus;
use App\Repository\ArtisanProfileRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/v1/autocomplete', name: 'api_autocomplete_')]
final class AutocompleteController
{
    public function __construct(
        private readonly ArtisanProfileRepository $artisanProfiles,
        private readonly CategoryRepository $categories,
    ) {
    }

    #[Route(path: '/cities', name: 'cities', methods: ['GET'])]
    public function cities(Request $request): JsonResponse
    {
        $q = (string) $request->query->get('q', '');
        $q = trim($q);

        // Validation d'entrée: q >= 2 (DoD)
        if (mb_strlen($q) < 2) {
            return new JsonResponse(['error' => 'invalid_query', 'message' => 'Parameter "q" must be at least 2 characters.'], 400);
        }

        // DISTINCT communes avec artisans vérifiés et au moins un service actif
        $em = $this->artisanProfiles->createQueryBuilder('a')
            ->select('LOWER(a.commune) AS city')
            ->innerJoin('a.artisanServices', 's')
            ->where('a.kycStatus = :kyc')
            ->andWhere('s.status = :active')
            ->andWhere('LOWER(a.commune) LIKE :q')
            ->setParameter('kyc', KycStatus::VERIFIED)
            ->setParameter('active', ArtisanServiceStatus::ACTIVE)
            ->setParameter('q', '%'.mb_strtolower($q).'%')
            ->groupBy('city')
            ->orderBy('city', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getScalarResult();

        $cities = array_map(static fn (array $row): string => (string) $row['city'], $em);

        return new JsonResponse($cities, 200);
    }

    #[Route(path: '/categories', name: 'categories', methods: ['GET'])]
    public function categories(Request $request): JsonResponse
    {
        $q = (string) $request->query->get('q', '');
        $q = trim($q);

        // Validation d'entrée: q >= 2 (DoD)
        if (mb_strlen($q) < 2) {
            return new JsonResponse(['error' => 'invalid_query', 'message' => 'Parameter "q" must be at least 2 characters.'], 400);
        }

        // Suggestions sur Category (slug ou name), réponse = tableau de slugs
        $qb = $this->categories->createQueryBuilder('c')
            ->select('LOWER(c.slug) AS slug')
            ->where('LOWER(c.name) LIKE :q OR LOWER(c.slug) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($q).'%')
            ->groupBy('slug')
            ->orderBy('slug', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getScalarResult();

        $slugs = array_map(static fn (array $row): string => (string) $row['slug'], $qb);

        return new JsonResponse($slugs, 200);
    }
}
