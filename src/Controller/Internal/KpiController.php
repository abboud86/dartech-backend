<?php

namespace App\Controller\Internal;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_internal', name: 'internal_')]
final class KpiController extends AbstractController
{
    #[Route('/_kpis', name: 'kpis', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $em): JsonResponse
    {
        $usersTotal = (int) $em->getRepository(User::class)
            ->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $artisansVerified = (int) $em->getRepository(ArtisanProfile::class)
            ->createQueryBuilder('ap')
            ->select('COUNT(ap.id)')
            ->andWhere('ap.kycStatus = :verified')
            ->setParameter('verified', 'verified')
            ->getQuery()->getSingleScalarResult();

        $servicesActive = (int) $em->getRepository(ArtisanService::class)
            ->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :active')
            ->setParameter('active', 'active')
            ->getQuery()->getSingleScalarResult();

        return $this->json([
            'users_total' => $usersTotal,
            'artisans_verified' => $artisansVerified,
            'services_published' => $servicesActive, // garde la clé 'services_published' pour ta page
        ]);
    }
}
