<?php

namespace App\Controller;

use App\Security\TokenRevoker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LogoutAllController extends AbstractController
{
    #[Route('/v1/auth/logout/all', name: 'auth_logout_all', methods: ['POST'])]
    public function __invoke(TokenRevoker $revoker): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED, [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]);
        }

        $result = $revoker->revokeAllFor($user);

        return new JsonResponse([
            'status' => $result['status'],
            'access_revoked' => $result['access_revoked'],
            'refresh_revoked' => $result['refresh_revoked'],
        ], Response::HTTP_OK, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
