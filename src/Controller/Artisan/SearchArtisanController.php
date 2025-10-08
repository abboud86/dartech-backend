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
        // Lire des entiers via l’API dédiée (best practice)
        $page = $request->query->getInt('page', self::DEFAULT_PAGE);
        $perPage = $request->query->getInt('per_page', self::DEFAULT_PER_PAGE);

        // Valider
        $constraints = new Assert\Collection([
            'page' => [
                new Assert\NotBlank(),
                new Assert\Type('integer'),
                new Assert\GreaterThanOrEqual(1),
            ],
            'per_page' => [
                new Assert\NotBlank(),
                new Assert\Type('integer'),
                new Assert\Range(min: 1, max: self::MAX_PER_PAGE),
            ],
        ]);

        $violations = $validator->validate(['page' => $page, 'per_page' => $perPage], $constraints);

        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => (string) $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return new JsonResponse(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'data' => [],
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ], JsonResponse::HTTP_OK);
    }
}
