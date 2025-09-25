<?php

namespace App\Security;

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
    public function supports(Request $request): ?bool
    {
        $header = $request->headers->get('Authorization', '');
        return str_starts_with($header, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = $request->headers->get('Authorization', '');
        $token  = trim(substr($header, 7));
        $valid  = $_ENV['API_DEV_TOKEN'] ?? 'dev-token';

        if ($token !== $valid) {
            throw new AuthenticationException('Invalid bearer token');
        }

        return new SelfValidatingPassport(
            new UserBadge('dev', function (string $identifier) {
                return new InMemoryUser($identifier, password: '', roles: ['ROLE_USER']);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // continuer la requête normalement
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => 'unauthorized'],
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }
}
