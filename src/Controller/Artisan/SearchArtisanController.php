<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SearchArtisanController
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;

    #[Route('/v1/artisans', name: 'api_artisans_index', methods: ['GET'])]
    public function __invoke(Request $request, ValidatorInterface $validator): JsonResponse
    {
        // Pagination
        $page = $request->query->getInt('page', self::DEFAULT_PAGE);
        $perPage = $request->query->getInt('per_page', self::DEFAULT_PER_PAGE);

        // Filtres (read-only)
        $filters = [
            'city' => $request->query->get('city'),
            'category' => $request->query->get('category'),
            'serviceDefinition' => $request->query->get('serviceDefinition'),
        ];

        // Contraintes (allowMissingFields pour filtres optionnels)
        $constraints = new Assert\Collection(
            fields: [
                'page' => new Assert\Sequentially([
                    new Assert\NotBlank(),
                    new Assert\Type('integer'),
                    new Assert\GreaterThanOrEqual(1),
                ]),
                'per_page' => new Assert\Sequentially([
                    new Assert\NotBlank(),
                    new Assert\Type('integer'),
                    new Assert\Range(min: 1, max: self::MAX_PER_PAGE),
                ]),
                'city' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(max: 64),
                ]),
                'category' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(max: 64),
                ]),
                'serviceDefinition' => new Assert\Optional([
                    new Assert\Type('string'),
                    new Assert\Length(max: 64),
                ]),
            ],
            allowMissingFields: true,
            allowExtraFields: true,
        );

        $input = [
            'page' => $page,
            'per_page' => $perPage,
            'city' => $filters['city'],
            'category' => $filters['category'],
            'serviceDefinition' => $filters['serviceDefinition'],
        ];

        $violations = $validator->validate($input, $constraints);

        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = [
                    'field' => (string) $v->getPropertyPath(),
                    'message' => $v->getMessage(),
                ];
            }

            return new JsonResponse(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Pas de logique métier ici (P2-04.3+ repo). On renvoie le contrat standard.
        return new JsonResponse([
            'data' => [],
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'filters' => array_filter($filters, static fn ($v) => null !== $v && '' !== $v),
            ],
        ], JsonResponse::HTTP_OK);
    }
}
