<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArtisanProfile;
use App\Enum\ArtisanServiceStatus;
use App\Enum\KycStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtisanProfile>
 */
final class ArtisanProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtisanProfile::class);
    }

    /**
     * @param array{city?:string,category?:string,serviceDefinition?:string} $filters
     *
     * @return Paginator<ArtisanProfile>
     */
    public function searchByFilters(array $filters, int $page, int $perPage, string $sort = 'relevance'): Paginator
    {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.artisanServices', 's')
            ->innerJoin('s.serviceDefinition', 'sd')
            ->innerJoin('sd.category', 'c')
            ->where('a.kycStatus = :kyc')
            ->andWhere('s.status = :active')
            ->setParameter('kyc', KycStatus::VERIFIED)
            ->setParameter('active', ArtisanServiceStatus::ACTIVE)
            ->groupBy('a.id');

        // Filtres
        if (!empty($filters['city'])) {
            $qb->andWhere('LOWER(a.commune) = LOWER(:city)')
               ->setParameter('city', $filters['city']);
        }
        if (!empty($filters['category'])) {
            $qb->andWhere('LOWER(c.slug) = LOWER(:cat)')
               ->setParameter('cat', $filters['category']);
        }
        if (!empty($filters['serviceDefinition'])) {
            $qb->andWhere('LOWER(sd.slug) = LOWER(:svc)')
               ->setParameter('svc', $filters['serviceDefinition']);
        }

        // Tri
        if ($sort === 'recent') {
            $qb->orderBy('a.createdAt', 'DESC')
               ->addOrderBy('a.id', 'DESC');
        } else {
            $qb->addSelect('COUNT(s.id) AS HIDDEN services_count')
               ->addSelect('MAX(s.publishedAt) AS HIDDEN last_pub')
               ->orderBy('services_count', 'DESC')
               ->addOrderBy('last_pub', 'DESC')
               ->addOrderBy('a.createdAt', 'DESC')
               ->addOrderBy('a.id', 'DESC');
        }

        // Pagination défensive
        $qb->setFirstResult(max(0, ($page - 1) * $perPage))
           ->setMaxResults(max(1, $perPage));

        return new Paginator($qb->getQuery(), true);
    }

    /**
     * Lecture publique: profil vérifié par slug, avec services actifs préchargés.
     * - Ne remonte que les profils KYC vérifiés.
     * - Précharge les services actifs (LEFT JOIN pour ne pas filtrer l'artisan si 0 service).
     */
    public function findOnePublicBySlug(string $slug): ?ArtisanProfile
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.artisanServices', 's', 'WITH', 's.status = :active')
            ->addSelect('s')
            ->where('a.slug = :slug')
            ->andWhere('a.kycStatus = :kyc')
            ->setParameter('slug', $slug)
            ->setParameter('kyc', KycStatus::VERIFIED)
            ->setParameter('active', ArtisanServiceStatus::ACTIVE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * ✅ Identifiant public strict: uniquement par slug.
     * Aucun alias via User.id (ULID) pour éviter d’exposer des identifiants internes.
     */
    public function findOneByPublicId(string $public): ?ArtisanProfile
    {
        $public = trim($public);
        if ($public === '') {
            return null;
        }

        return $this->findOneBy(['slug' => $public]);
    }

    /**
     * Prévisualisation du portfolio (≤ $limit).
     * Retourne une liste d'URL publiques (vide si aucune source).
     *
     * @return list<string>
     */
    public function findPortfolioPreview(ArtisanProfile $artisan, int $limit = 4): array
    {
        if ($limit <= 0) {
            return [];
        }

        // Placeholder contrôlé tant que la source réelle (Media/Portfolio) n'est pas branchée côté domain.
        return [];
    }
}
