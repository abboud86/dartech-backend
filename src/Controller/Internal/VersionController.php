<?php

namespace App\Controller\Internal;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_internal', name: 'internal_')]
final class VersionController extends AbstractController
{
    public function __construct(
        private readonly string $appVersion = 'unknown',
        private readonly string $envName = 'dev',
    ) {
    }

    #[Route('/_version', name: 'version', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'version' => $this->appVersion,
            'env' => $this->envName,
            'time' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }
}
