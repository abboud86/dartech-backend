<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ArtisanProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/artisans', name: 'admin_artisans_')]
final class ArtisanAdminController extends AbstractController
{
    public function __construct(
        private readonly ArtisanProfileRepository $profiles,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $commune = trim((string) $request->query->get('commune', ''));

        $qb = $this->profiles->createQueryBuilder('ap')
            ->innerJoin('ap.artisanServices', 's', 'WITH', 's.status = :active')
            ->andWhere('ap.kycStatus = :verified')
            ->setParameter('verified', 'verified')
            ->setParameter('active', 'active');

        if ('' !== $commune) {
            $qb->andWhere('LOWER(ap.commune) = :commune')
               ->setParameter('commune', mb_strtolower($commune));
        }

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT ap.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $offset = ($page - 1) * $limit;

        $items = $qb
            ->groupBy('ap.id')
            ->orderBy('ap.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/artisans.html.twig', [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'hasPrev' => $page > 1,
                'hasNext' => ($offset + $limit) < $total,
            ],
            'filters' => [
                'commune' => $commune,
            ],
        ]);
    }
}
