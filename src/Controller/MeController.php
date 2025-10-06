<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class MeController extends AbstractController
{
    #[Route('/v1/me', name: 'api_me', methods: ['GET'])]
    public function me(
        Security $security,
        #[Autowire(service: 'limiter.me_get')] RateLimiterFactory $meGetLimiter,
    ): JsonResponse {
        // 401 si non authentifié
        $user = $security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'unauthorized'], 401, [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]);
        }

        // 403 si pas de profil (ne pas consommer le quota pour un état interdit)
        $ap = $user->getArtisanProfile();
        if (null === $ap) {
            return new JsonResponse(['error' => 'forbidden', 'detail' => 'profile_required'], 403, [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]);
        }

        // 429 : 2 req / minute / user (après 401/403)
        $limit = $meGetLimiter->create($user->getUserIdentifier())->consume(1);
        if (!$limit->isAccepted()) {
            return new JsonResponse(['error' => 'too_many_requests'], 429, [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]);
        }

        // Payload minimal
        $data = [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'artisan_profile' => [
                'id' => (string) $ap->getId(),
                'display_name' => $ap->getDisplayName(),
                'phone' => $ap->getPhone(),
                'wilaya' => $ap->getWilaya(),
                'commune' => $ap->getCommune(),
                'kyc_status' => $ap->getKycStatus()->value,
            ],
        ];

        return new JsonResponse($data, 200, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
