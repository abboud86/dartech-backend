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

    #[Route('/catalogue', name: 'catalogue_index', methods: ['GET'])]
    public function catalogue(
        CategoryRepository $catRepo,
        ServiceDefinitionRepository $svcRepo,
    ): JsonResponse {
        // 1) Charger toutes les catégories (ordre: parent, puis nom)
        $categories = $catRepo->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')->addSelect('p')
            ->orderBy('p.id', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        // 2) Préparer la map [categoryId => row JSON]
        $byId = [];
        foreach ($categories as $c) {
            $cid = (string) $c->getId();

            $byId[$cid] = [
                'id' => $cid,
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'parentId' => $c->getParent() ? (string) $c->getParent()->getId() : null,
                'services' => [],
            ];
        }

        // 3) Charger tous les services avec leur catégorie (ordre: catégorie, service)
        $services = $svcRepo->createQueryBuilder('s')
            ->join('s.category', 'c')->addSelect('c')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($services as $s) {
            $cat = $s->getCategory();
            if (!$cat) {
                continue;
            }
            $cid = (string) $cat->getId();
            if (!isset($byId[$cid])) {
                // Sécurité : si une cat n’était pas dans la première requête
                $byId[$cid] = [
                    'id' => $cid,
                    'name' => $cat->getName(),
                    'slug' => $cat->getSlug(),
                    'parentId' => $cat->getParent()?->getId(),
                    'services' => [],
                ];
            }
            $byId[$cid]['services'][] = [
                'id' => (string) $s->getId(),
                'name' => $s->getName(),
                'slug' => $s->getSlug(),
            ];
        }

        // 4) Sortie ordonnée (selon l’ordre des catégories déjà triées)
        $data = array_values($byId);

        return new JsonResponse(['data' => $data], Response::HTTP_OK);
    }
}
