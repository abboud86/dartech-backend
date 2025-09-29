<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/v1', name: 'api_v1_')]
final class CatalogueController extends AbstractController
{
    #[Route('/categories/ping', name: 'categories_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        // Juste pour valider le contrôleur/PSR-4 ; on remplacera par /v1/categories ensuite
        return $this->json(['ok' => true]);
    }

    #[Route('/categories', name: 'categories_index', methods: ['GET'])]
    public function listCategories(CategoryRepository $repo): JsonResponse
    {
        $cats = $repo->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')
            ->addSelect('p')
            ->orderBy('c.parent', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
        $data = array_map(static function ($c) {
            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'parentId' => $c->getParent()?->getId(),
            ];
        }, $cats);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }
}
