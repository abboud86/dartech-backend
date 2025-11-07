<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\User;
use App\Enum\CommunicationChannel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

final class BookingPatchController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route(
        path: '/api/bookings/{id}',
        name: 'api_bookings_patch',
        methods: ['PATCH'],
        priority: -10
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        // 1) JSON d'abord (pour que invalid_json gagne sur un id moche)
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['error' => 'invalid_json'], 400);
        }

        // 2) Validation de l'ULID (même pattern que GET / Transition)
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

        // P3-02-06 (ownership) : on ajoutera plus tard booking->getClient() === $user

        // communication_channel optionnel
        if (\array_key_exists('communication_channel', $payload)) {
            $channelValue = $payload['communication_channel'];

            if (null === $channelValue) {
                // null => on ne touche pas pour l’instant (P3-02)
            } else {
                if (!\is_string($channelValue) || '' === $channelValue) {
                    return $this->json(['error' => 'invalid_communication_channel'], 400);
                }

                try {
                    $communicationChannel = CommunicationChannel::from($channelValue);
                } catch (\ValueError) {
                    return $this->json(['error' => 'invalid_communication_channel'], 400);
                }

                $booking->setCommunicationChannel($communicationChannel);
            }
        }

        // scheduled_at optionnel
        if (\array_key_exists('scheduled_at', $payload)) {
            $rawScheduledAt = $payload['scheduled_at'];

            if (null === $rawScheduledAt || '' === $rawScheduledAt) {
                // Interprété comme "ne pas modifier" pour P3-02
            } else {
                if (!\is_string($rawScheduledAt)) {
                    return $this->json(['error' => 'invalid_scheduled_at'], 400);
                }

                try {
                    $scheduledAt = new \DateTimeImmutable($rawScheduledAt);
                } catch (\Throwable) {
                    return $this->json(['error' => 'invalid_scheduled_at'], 400);
                }

                $booking->setScheduledAt($scheduledAt);
            }
        }

        // estimated_amount optionnel (même logique que POST)
        if (\array_key_exists('estimated_amount', $payload)) {
            $rawAmount = $payload['estimated_amount'];

            if (null !== $rawAmount) {
                $amount = $rawAmount;

                if (\is_int($amount) || \is_float($amount)) {
                    $amount = (string) $amount;
                }

                if (!\is_string($amount)) {
                    return $this->json(['error' => 'invalid_estimated_amount'], 400);
                }

                $booking->setEstimatedAmount($amount);
            }
        }

        // On ne touche pas au statut ici (PATCH != transition de workflow)
        $this->em->flush();

        return $this->json([
            'id' => (string) $booking->getId(),
            'status' => $booking->getStatusMarking(),
            'scheduled_at' => $booking->getScheduledAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $booking->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}
