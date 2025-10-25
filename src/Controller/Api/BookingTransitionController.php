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
use Symfony\Component\Workflow\Registry as WorkflowRegistry;

final class BookingTransitionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowRegistry $workflows,
    ) {
    }

    #[Route(path: '/api/bookings/{id}/transition', name: 'api_booking_apply_transition', methods: ['POST'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['error' => 'invalid_json'], 400);
        }

        $transition = isset($payload['transition']) && \is_string($payload['transition'])
            ? $payload['transition']
            : null;

        if (null === $transition || '' === $transition) {
            return $this->json(['error' => 'missing_transition'], 400);
        }

        try {
            $ulid = new Ulid($id);
        } catch (\Throwable) {
            return $this->json(['error' => 'invalid_booking_id'], 400);
        }

        /** @var Booking|null $booking */
        $booking = $this->em->getRepository(Booking::class)->find($ulid);
        if (!$booking) {
            return $this->json(['error' => 'booking_not_found'], 404);
        }

        // Récupère la state machine via le Registry (doc officielle)
        $sm = $this->workflows->get($booking, 'booking');

        if (!$sm->can($booking, $transition)) {
            return $this->json([
                'error' => 'transition_not_allowed',
                'current_status' => $booking->getStatusMarking(),
                'requested' => $transition,
            ], 409);
        }

        $sm->apply($booking, $transition);
        $this->em->flush();

        return $this->json([
            'id' => (string) $booking->getId(),
            'status' => $booking->getStatusMarking(),
            'applied' => $transition,
        ]);
    }
}
