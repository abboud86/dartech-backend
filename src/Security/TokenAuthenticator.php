<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private LoggerInterface $securityLogger)
    {
    }

    public function supports(Request $request): bool
    {
        // Cast to string and check prefix; no is_string() needed
        $h = (string) $request->headers->get('Authorization', '');

        return str_starts_with($h, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = (string) $request->headers->get('Authorization', '');
        $token = str_starts_with($header, 'Bearer ') ? trim(substr($header, 7)) : '';
        $valid = (string) ($_ENV['API_DEV_TOKEN'] ?? 'dev-token');

        if ('' === $token || strlen($token) > 1024) {
            throw new AuthenticationException('Invalid bearer token');
        }

        // constant-time compare
        if (!hash_equals($valid, $token)) {
            throw new AuthenticationException('Invalid bearer token');
        }

        return new SelfValidatingPassport(
            new UserBadge('dev', static fn (string $id) => new InMemoryUser($id, '', ['ROLE_USER']))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->securityLogger->warning('auth_failure', [
            'path' => $request->getPathInfo(),
            'ip' => $request->getClientIp(),
            'reason' => $exception->getMessage(),
            'has_header' => $request->headers->has('Authorization'),
        ]);

        return new JsonResponse(
            ['error' => 'unauthorized'],
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }
}
