<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

final class BookingGetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route(
        path: '/api/bookings/{id}',
        name: 'api_bookings_get',
        methods: ['GET'],
        priority: -10
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        // Validation de l'ULID
        try {
            $ulid = new Ulid($id);
        } catch (\Throwable) {
            return $this->json(['error' => 'invalid_booking_id'], 400);
        }

        /** @var Booking|null $booking */
        $booking = $this->em->getRepository(Booking::class)->find($ulid);
        if (null === $booking) {
            return $this->json(['error' => 'booking_not_found'], 404);
        }

        // 🔒 Ownership : un utilisateur ne peut voir que ses propres bookings
        if ($booking->getClient() !== $user) {
            return $this->json(['error' => 'forbidden'], 403);
        }

        return $this->json([
            'id' => (string) $booking->getId(),
            'status' => $booking->getStatusMarking(),
            'scheduled_at' => $booking->getScheduledAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $booking->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}
