<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArtisanProfile;
use App\Enum\ArtisanServiceStatus;
use App\Enum\KycStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

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
        if ('recent' === $sort) {
            // Récents = profils les plus récents (créés récemment)
            $qb->orderBy('a.createdAt', 'DESC')
               ->addOrderBy('a.id', 'DESC');
        } else {
            // Pertinence = nb de services actifs, puis dernière publication, puis fraicheur du profil
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
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.artisanServices', 's', 'WITH', 's.status = :active')
            ->addSelect('s')
            ->where('a.slug = :slug')
            ->andWhere('a.kycStatus = :kyc')
            ->setParameter('slug', $slug)
            ->setParameter('kyc', KycStatus::VERIFIED)
            ->setParameter('active', ArtisanServiceStatus::ACTIVE)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Lecture publique par identifiant public: User.id (ULID).
     * - Profil KYC vérifié
     * - Précharge services actifs en LEFT JOIN (ne filtre pas l'artisan s'il n'en a pas).
     */
    public function findOnePublicByPublicId(string $publicId): ?ArtisanProfile
    {
        // Si le slug n'est pas un ULID valide → 404 (on renvoie null)
        try {
            $ulid = new Ulid($publicId);
        } catch (\Throwable) {
            return null;
        }

        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.user', 'u')
            ->leftJoin('a.artisanServices', 's', 'WITH', 's.status = :active')
            ->addSelect('s')
            ->andWhere('u.id = :uid')
            ->andWhere('a.kycStatus = :kyc')
            ->setParameter('uid', $ulid, 'ulid') // ✅ binder en type "ulid"
            ->setParameter('kyc', KycStatus::VERIFIED)
            ->setParameter('active', ArtisanServiceStatus::ACTIVE)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Méthode unifiée pour les contrôleurs : tente d'abord par slug,
     * sinon alias legacy via User.id (ULID). Ne force pas la condition KYC.
     */
    public function findOneByPublicId(string $public): ?ArtisanProfile
    {
        $public = trim($public);
        if ('' === $public) {
            return null;
        }

        // 1) Slug direct
        $bySlug = $this->findOneBy(['slug' => $public]);
        if ($bySlug instanceof ArtisanProfile) {
            return $bySlug;
        }

        // 2) Alias legacy : User.id (ULID)
        if (Ulid::isValid($public)) {
            return $this->createQueryBuilder('a')
                ->innerJoin('a.user', 'u')
                ->andWhere('u.id = :uid')
                ->setParameter('uid', $public, 'ulid')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return null;
    }

    /**
     * Lecture d'une prévisualisation du portfolio (≤ $limit).
     * Zéro supposition tant qu'aucune entité média n'existe : on renvoie une liste vide.
     *
     * @return list<string> URLs publiques de médias
     */
    public function findPortfolioPreview(ArtisanProfile $artisan, int $limit = 4): array
    {
        // Placeholder contrôlé : retour vide en attendant la vraie source (entité Media/Portfolio).
        // Invariants :
        //  - $limit >= 0
        //  - tableau indexé (list<string>)
        if ($limit <= 0) {
            return [];
        }

        return [];
    }

    /*
     * Lecture publique par ID public (User.id ULID), profil KYC VERIFIED + services actifs préchargés.
     * - Convertit le slug string en Ulid et le lie au type Doctrine "ulid" (PostgreSQL = uuid en BDD).
     */
}
