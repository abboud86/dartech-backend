<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }

    #[Route('/v1/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function registerPlaceholder(): JsonResponse
    {
        return new JsonResponse(['status' => 'not_implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
