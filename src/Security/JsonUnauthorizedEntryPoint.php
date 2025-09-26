<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class JsonUnauthorizedEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private LoggerInterface $securityLogger,
    ) {}

    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        // Log sécurité (channel=security) : utile en prod (stderr JSON)
        $this->securityLogger->warning('unauthorized_access', [
            'path' => $request->getPathInfo(),
            'ip'   => $request->getClientIp(),
        ]);

        return new JsonResponse(
            ['error' => 'unauthorized'],
            JsonResponse::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }
}
