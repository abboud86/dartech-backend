<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MeController extends AbstractController
{
    #[Route('/v1/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'unauthorized'],
                401,
                ['Content-Type' => 'application/json; charset=UTF-8']
            );
        }

        $ap = $user->getArtisanProfile();

        $payload = [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
        ];

        if (null !== $ap) {
            // KycStatus est un Backed Enum → utilisons directement ->value (ex: "pending")
            $payload['artisan_profile'] = [
                'display_name' => $ap->getDisplayName(),
                'phone' => $ap->getPhone(),
                'wilaya' => $ap->getWilaya(),
                'commune' => $ap->getCommune(),
                'kyc_status' => $ap->getKycStatus()->value,
            ];
        } else {
            $payload['artisan_profile'] = null;
        }

        return new JsonResponse(
            $payload,
            200,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }
}
