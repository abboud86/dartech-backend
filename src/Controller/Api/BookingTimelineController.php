<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\BookingTimeline;
use App\Entity\User;
use App\Repository\Contract\BookingReadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BookingTimelineController extends AbstractController
{
    public function __construct(
        private readonly BookingReadRepository $readRepo,
    ) {
    }

    #[Route(path: '/api/bookings/{id}/timeline', name: 'api_booking_timeline', methods: ['GET'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $size = max(1, min(100, $request->query->getInt('size', 20)));

        /** @var list<BookingTimeline> $items */
        $items = $this->readRepo->findTimelineForBooking($id, $page, $size);
        $total = $this->readRepo->countTimelineForBooking($id);

        $data = array_map(
            static function (BookingTimeline $t): array {
                $row = [
                    'id' => (string) $t->getId(),
                    'from' => $t->getFromStatus(),
                    'to' => $t->getToStatus(),
                    'occurred_at' => $t->getOccurredAt()->format(\DateTimeInterface::ATOM),
                ];

                // Expose actor uniquement s’il existe (ne casse pas les tests actuels)
                if (null !== ($actor = $t->getActor())) {
                    $row['actor'] = [
                        'id' => (string) $actor->getId(),
                        // ajouter d’autres champs plus tard si besoin
                    ];
                }

                return $row;
            },
            $items
        );

        return $this->json([
            'page' => $page,
            'size' => $size,
            'total' => $total,
            'items' => $data,
        ]);
    }
}
