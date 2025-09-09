<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;         // ✅ import requis
use Symfony\Component\Routing\Attribute\Route;              // ✅ attributes (syntaxe moderne)

final class SecurePingController extends AbstractController
{
    #[Route('/api/private/ping', name: 'api_private_ping', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['ok' => true]);
    }
}
