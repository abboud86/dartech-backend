<?php

namespace App\Controller\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_internal', name: 'internal_')]
final class KpiController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/_kpis', name: 'kpis', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $usersTotal = (int) $this->em
            ->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u')
            ->getSingleScalarResult();

        $artisansVerified = (int) $this->em
            ->createQuery('SELECT COUNT(ap.id) FROM App\Entity\ArtisanProfile ap WHERE ap.kycStatus = :status')
            ->setParameter('status', 'verified')
            ->getSingleScalarResult();

        $servicesPublished = (int) $this->em
            ->createQuery('SELECT COUNT(s.id) FROM App\Entity\ArtisanService s WHERE s.status = :published')
            ->setParameter('published', 'published')
            ->getSingleScalarResult();

        return $this->json([
            'users_total' => $usersTotal,
            'artisans_verified' => $artisansVerified,
            'services_published' => $servicesPublished,
        ]);
    }
}
