<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\Contract\BookingReadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BookingListController extends AbstractController
{
    public function __construct(
        private readonly BookingReadRepository $readRepo,
    ) {
    }

    #[Route(path: '/api/bookings/me', name: 'api_bookings_me', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        //  Utiliser getInt() (doc HttpFoundation) plutôt que get() avec défaut int
        $page = max(1, $request->query->getInt('page', 1));
        $size = max(1, min(100, $request->query->getInt('size', 20)));

        $clientIdUlid = (string) $user->getId(); // ULID app-side

        $items = $this->readRepo->findForClientPaginated($clientIdUlid, $page, $size);
        $total = $this->readRepo->countByClient($clientIdUlid);

        $data = array_map(
            static function (Booking $b): array {
                return [
                    'id' => (string) $b->getId(),
                    'status' => $b->getStatusMarking(), // string pour compat workflow
                    'scheduled_at' => $b->getScheduledAt()?->format(\DateTimeInterface::ATOM),
                    'created_at' => $b->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    'updated_at' => $b->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
                ];
            },
            $items
        );

        return $this->json([
            'page' => $page,
            'size' => $size,
            'total' => $total,
            'pages' => (int) \ceil($total / $size),
            'items' => $data,
        ]);
    }
}
