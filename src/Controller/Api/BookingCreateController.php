<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ArtisanService;
use App\Entity\Booking;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\CommunicationChannel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BookingCreateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route(path: '/api/bookings', name: 'api_bookings_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        // Parse JSON body
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['error' => 'invalid_json'], 400);
        }

        // artisan_service_id obligatoire
        $artisanServiceId = $payload['artisan_service_id'] ?? null;
        if (!\is_string($artisanServiceId) || '' === $artisanServiceId) {
            return $this->json(['error' => 'missing_artisan_service_id'], 400);
        }

        // communication_channel obligatoire
        $channelValue = $payload['communication_channel'] ?? null;
        if (!\is_string($channelValue) || '' === $channelValue) {
            return $this->json(['error' => 'missing_communication_channel'], 400);
        }

        // Validation brute du channel dans P3-02
        try {
            $communicationChannel = CommunicationChannel::from($channelValue);
        } catch (\ValueError) {
            return $this->json(['error' => 'invalid_communication_channel'], 400);
        }

        // Récupération de l'ArtisanService
        /** @var ArtisanService|null $artisanService */
        $artisanService = $this->em->getRepository(ArtisanService::class)->find($artisanServiceId);
        if (null === $artisanService) {
            return $this->json(['error' => 'service_not_found'], 404);
        }

        // Création du Booking
        $booking = new Booking();
        $booking->setClient($user);
        $booking->setArtisanService($artisanService);
        $booking->setStatus(BookingStatus::INQUIRY); // initial_marking cohérent avec workflow

        $booking->setCommunicationChannel($communicationChannel);

        // scheduled_at optionnel
        $rawScheduledAt = $payload['scheduled_at'] ?? null;
        if (null !== $rawScheduledAt && '' !== $rawScheduledAt) {
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

        // estimated_amount optionnel (P3-02 : on accepte un entier ou une string simple)
        if (\array_key_exists('estimated_amount', $payload)) {
            $rawAmount = $payload['estimated_amount'];

            // null explicite => on n'affecte rien
            if (null !== $rawAmount) {
                $amount = $rawAmount;

                if (\is_int($amount) || \is_float($amount)) {
                    $amount = (string) $amount;
                }

                if (!\is_string($amount)) {
                    return $this->json(['error' => 'invalid_estimated_amount'], 400);
                }

                // Pas de validation métier avancée ici (P3-02-05)
                $booking->setEstimatedAmount($amount);
            }
        }

        $this->em->persist($booking);
        $this->em->flush();

        // Réponse alignée sur BookingListController
        return $this->json(
            [
                'id' => (string) $booking->getId(),
                'status' => $booking->getStatusMarking(),
                'scheduled_at' => $booking->getScheduledAt()?->format(\DateTimeInterface::ATOM),
                'created_at' => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $booking->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            ],
            201
        );
    }
}
