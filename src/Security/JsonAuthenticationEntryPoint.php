<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class JsonAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        $requestId = $request->headers->get('X-Request-Id');

        return new JsonResponse([
            'error' => [
                'code' => 401,
                'message' => 'Authentication required',
                'details' => 'Please authenticate to access this resource.',
                'request_id' => $requestId,
            ],
        ], 401);
    }
}
