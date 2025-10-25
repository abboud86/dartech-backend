<?php

declare(strict_types=1);

namespace App\Repository\Doctrine;

use App\Entity\Booking;
use App\Entity\BookingTimeline;
use App\Repository\Contract\BookingReadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final class DoctrineBookingReadRepository implements BookingReadRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return list<Booking>
     */
    public function findForClientPaginated(string $clientIdUlid, int $page, int $size): array
    {
        $page = max(1, $page);
        $size = max(1, $size);

        $qb = $this->em->createQueryBuilder()
            ->select('b')
            ->from(Booking::class, 'b')
            // ✅ comparer par identifiant + typer en 'ulid' pour éviter ULID base32 → uuid mismatch
            ->andWhere('IDENTITY(b.client) = :clientId')
            ->setParameter('clientId', new Ulid($clientIdUlid), 'ulid')
            ->orderBy('b.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $size)
            ->setMaxResults($size);

        /* @var list<Booking> $results */
        return $qb->getQuery()->getResult();
    }

    public function countByClient(string $clientIdUlid): int
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(b.id)')
            ->from(Booking::class, 'b')
            // ✅ idem: identifiant + type ulid
            ->andWhere('IDENTITY(b.client) = :clientId')
            ->setParameter('clientId', new Ulid($clientIdUlid), 'ulid');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<BookingTimeline>
     */
    public function findTimelineForBooking(string $bookingIdUlid, int $page, int $size): array
    {
        $page = max(1, $page);
        $size = max(1, $size);

        $qb = $this->em->createQueryBuilder()
            ->select('t')
            ->from(BookingTimeline::class, 't')
            ->andWhere('IDENTITY(t.booking) = :bookingId')
            ->setParameter('bookingId', new Ulid($bookingIdUlid), 'ulid')
            // ✅ ordre chronologique (le test attend ASC)
            ->orderBy('t.occurredAt', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->setFirstResult(($page - 1) * $size)
            ->setMaxResults($size);

        /* @var list<BookingTimeline> $results */
        return $qb->getQuery()->getResult();
    }

    public function countTimelineForBooking(string $bookingIdUlid): int
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(BookingTimeline::class, 't')
            ->andWhere('IDENTITY(t.booking) = :bookingId')
            ->setParameter('bookingId', new Ulid($bookingIdUlid), 'ulid');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
