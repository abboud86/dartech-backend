<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiLoginController extends AbstractController
{
    // POST uniquement, c’est notre endpoint de login JSON
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function __invoke(): Response
    {
        // ℹ️ Ce contrôleur est exécuté SEULEMENT si l’authentification réussit.
        // Pour l’instant, on renvoie juste un JSON de placeholder.
        return $this->json(['status' => 'ok']);
    }
}
