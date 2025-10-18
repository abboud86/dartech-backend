<?php

declare(strict_types=1);

namespace App\Controller\Internal;

use App\Entity\ArtisanProfile;
use App\Repository\ArtisanProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_internal', name: 'internal_')]
final class ArtisanInternalController extends AbstractController
{
    public function __construct(
        private readonly ArtisanProfileRepository $profiles,
    ) {
    }

    /**
     * Liste read-only des artisans :
     *  - KYC = verified
     *  - avec ≥ 1 service publié
     *  - filtre par commune (optionnel)
     *  - pagination page/limit
     */
    #[Route('/artisans', name: 'artisans', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $commune = trim((string) $request->query->get('commune', ''));

        // Base query: profils avec au moins un service "published" + KYC "verified"
        $qb = $this->profiles->createQueryBuilder('ap')
            ->innerJoin('ap.artisanServices', 's', 'WITH', 's.status = :active')
            ->andWhere('ap.kycStatus = :verified')
            ->setParameter('verified', 'verified')
            ->setParameter('active', 'active');

        if ('' !== $commune) {
            $qb->andWhere('LOWER(ap.commune) = :commune')
               ->setParameter('commune', mb_strtolower($commune));
        }

        // Total DISTINCT
        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT ap.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        // Pagination
        $offset = ($page - 1) * $limit;

        $items = $qb
            ->groupBy('ap.id')
            ->orderBy('ap.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $response = $this->render('internal/artisans.html.twig', [
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

        // Internal-only: pas de cache navigateur
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    /**
     * Fiche read-only d’un artisan (KYC verified + services publiés).
     * NB: on force la contrainte via la requête pour éviter l’accès à un profil non conforme.
     */
    #[Route('/artisans/{id}', name: 'artisan_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $qb = $this->profiles->createQueryBuilder('ap')
    ->leftJoin('ap.artisanServices', 's', 'WITH', 's.status = :active')
    ->addSelect('s')
    ->andWhere('ap.kycStatus = :verified')
    ->andWhere('ap.id = :id')
    ->setParameter('verified', 'verified')
    ->setParameter('active', 'active')
    ->setParameter('id', $id)
    ->setMaxResults(1);

        /** @var ArtisanProfile|null $profile */
        $profile = $qb->getQuery()->getOneOrNullResult();

        if (null === $profile) {
            throw $this->createNotFoundException('Artisan introuvable ou non éligible.');
        }

        $response = $this->render('internal/artisan_show.html.twig', [
            'artisan' => $profile,
            'services' => $profile->getArtisanServices(), // filtrés published par le LEFT JOIN WITH
        ]);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
