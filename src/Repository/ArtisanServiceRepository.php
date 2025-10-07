<?php

namespace App\Repository;

use App\Entity\ArtisanProfile;
use App\Entity\ArtisanService;
use App\Entity\ServiceDefinition;
use App\Enum\ArtisanServiceStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<ArtisanService>
 */
class ArtisanServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtisanService::class);
    }

    /**
     * Returns true if an ACTIVE service already exists for (artisanProfile, serviceDefinition).
     * $excludeId lets you exclude the current entity (edition).
     */
    public function hasActiveForCouple(
        ArtisanProfile $artisanProfile,
        ServiceDefinition $serviceDefinition,
        ?Ulid $excludeId = null,
    ): bool {
        $qb = $this->createQueryBuilder('s')
            ->select('1')
            ->andWhere('s.artisanProfile = :ap')
            ->andWhere('s.serviceDefinition = :sd')
            ->andWhere('s.status = :active')
            ->setParameter('ap', $artisanProfile)
            ->setParameter('sd', $serviceDefinition)
            ->setParameter('active', ArtisanServiceStatus::ACTIVE)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb->andWhere('s.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }
}
