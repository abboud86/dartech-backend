<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ProtectedPingController
{
    #[Route('/v1/ping', name: 'protected_ping', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['ok' => true]);
    }
}
