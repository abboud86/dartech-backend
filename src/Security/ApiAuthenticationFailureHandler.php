<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class ApiAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $e): JsonResponse
    {
        $tooMany = $e instanceof TooManyLoginAttemptsAuthenticationException;
        $status = $tooMany ? 429 : 401;

        // Message standardisé recommandé par la doc
        $message = $e->getMessageKey();
        $data = $e->getMessageData();

        return new JsonResponse([
            'error' => [
                'code' => $status,
                'message' => $message,
                'context' => $data,
                'request_id' => $request->headers->get('X-Request-Id'),
            ],
        ], $status);
    }
}
