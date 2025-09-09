<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SecurePingController extends AbstractController
{
   #[Route('/api/private/ping', name: 'api_private_ping', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['ok' => true]);
    }
}
