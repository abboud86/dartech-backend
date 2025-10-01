<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class TestTokenAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): bool
    {
        // Actif uniquement si l'en-tête X-Test-User est présent
        return $request->headers->has('X-Test-User');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $email = $request->headers->get('X-Test-User');
        if (!$email) {
            throw new AuthenticationException('Missing X-Test-User header');
        }

        // UserBadge utilise le UserProvider (UserRepository::loadUserByIdentifier)
        return new SelfValidatingPassport(new UserBadge($email));
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        // Poursuivre la requête
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
