<?php

namespace App\Repository;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\ServiceDefinition;
use App\Enum\ArtisanServiceStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArtisanServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtisanService::class);
    }

    /**
     * Returns another active service for the same (artisan, serviceDefinition),
     * excluding the current one (by ULID) if provided.
     */
    public function findOneActiveByArtisanAndDefinition(
        ArtisanProfile $artisan,
        ServiceDefinition $definition,
        ?\Symfony\Component\Uid\Ulid $excludeId = null,
    ): ?ArtisanService {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.artisanProfile = :artisan')
            ->andWhere('s.serviceDefinition = :definition')
            ->andWhere('s.status = :status')
            ->setParameter('artisan', $artisan)
            ->setParameter('definition', $definition)
            ->setParameter('status', ArtisanServiceStatus::ACTIVE);

        if (null !== $excludeId) {
            $qb->andWhere('s.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
}
