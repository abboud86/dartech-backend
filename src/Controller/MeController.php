<?php

namespace App\Controller;

use App\Dto\MeResponseMapper;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MeController extends AbstractController
{
    #[Route('/v1/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(
        #[CurrentUser] ?User $user,
        MeResponseMapper $mapper,
    ): JsonResponse {
        if (null === $user) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        $dto = $mapper->map($user);

        return $this->json($dto->toArray(), 200);
    }
}
