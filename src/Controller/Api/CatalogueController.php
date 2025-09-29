<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use App\Repository\ServiceDefinitionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/v1', name: 'api_v1_')]
final class CatalogueController extends AbstractController
{
    #[Route('/categories/ping', name: 'categories_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        // Just to validate the controller/PSR-4; will be replaced by /v1/categories later
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

    #[Route('/services', name: 'services_index', methods: ['GET'])]
    public function listServices(
        Request $request,
        ServiceDefinitionRepository $serviceRepo,
        CategoryRepository $categoryRepo,
    ): JsonResponse {
        $page = max(1, (int) $request->query->getInt('page', 1));
        $limit = (int) $request->query->getInt('limit', 20);
        $limit = min(max($limit, 1), 100);

        $categoryFilter = $request->query->get('category'); // string|null

        $qb = $serviceRepo->createQueryBuilder('s')
            ->join('s.category', 'c')
            ->addSelect('c')
            ->orderBy('s.name', 'ASC');

        if (null !== $categoryFilter && '' !== $categoryFilter) {
            if (ctype_digit((string) $categoryFilter)) {
                $qb->andWhere('c.id = :catId')->setParameter('catId', (int) $categoryFilter);
            } else {
                $qb->andWhere('LOWER(c.slug) = :catSlug')->setParameter('catSlug', strtolower((string) $categoryFilter));
            }
        }

        // TOTAL
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(s.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        // PAGE
        $offset = ($page - 1) * $limit;
        $items = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        $data = array_map(static function ($s) {
            return [
                'id' => $s->getId(),
                'name' => $s->getName(),
                'slug' => $s->getSlug(),
                'category' => [
                    'id' => $s->getCategory()->getId(),
                    'slug' => $s->getCategory()->getSlug(),
                ],
            ];
        }, $items);

        $pages = (int) ceil($total / $limit);

        return new JsonResponse([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages,
            ],
        ], Response::HTTP_OK);
    }
}
