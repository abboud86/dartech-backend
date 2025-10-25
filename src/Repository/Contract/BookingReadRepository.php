<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Entity\Booking;
use App\Entity\BookingTimeline;

/**
 * Contrat de lecture Booking (pagination & timeline).
 */
interface BookingReadRepository
{
    /**
     * @return list<Booking>
     */
    public function findForClientPaginated(string $clientIdUlid, int $page, int $size): array;

    public function countByClient(string $clientIdUlid): int;

    /**
     * @return list<BookingTimeline>
     */
    public function findTimelineForBooking(string $bookingIdUlid, int $page, int $size): array;

    /**
     * Compte total des évènements de timeline pour un booking.
     */
    public function countTimelineForBooking(string $bookingIdUlid): int;
}
