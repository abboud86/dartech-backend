<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\ArtisanServiceStatus;
use App\Repository\ArtisanServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/services', name: 'admin_services_')]
final class ServiceAdminController extends AbstractController
{
    public function __construct(
        private readonly ArtisanServiceRepository $services,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));

        $qb = $this->services->createQueryBuilder('s')
            ->innerJoin('s.artisanProfile', 'ap')
            ->addSelect('ap')
            ->andWhere('s.status = :active')
            ->setParameter('active', ArtisanServiceStatus::ACTIVE);

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT s.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $offset = ($page - 1) * $limit;

        $items = $qb
            ->groupBy('s.id')
            ->orderBy('s.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/services.html.twig', [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'hasPrev' => $page > 1,
                'hasNext' => ($offset + $limit) < $total,
            ],
        ]);
    }
}
