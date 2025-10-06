<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\TokenIssuer;
use App\Security\TokenRevoker;
use App\Security\TokenRotator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    #[Route('/v1/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $violations = $validator->validate($data, new Assert\Collection(fields: [
            'email' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
            'password' => [new Assert\NotBlank(), new Assert\Length(min: 8)],
        ]));
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => (string) $v->getPropertyPath(), 'message' => $v->getMessage()];
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $email = (string) $data['email'];
        $plain = (string) $data['password'];

        if (null !== $users->findOneBy(['email' => $email])) {
            return new JsonResponse(['error' => 'email_already_exists'], Response::HTTP_CONFLICT);
        }

        $user = (new User())->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $plain));

        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/v1/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
        TokenIssuer $issuer,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $violations = $validator->validate($data, new Assert\Collection(fields: [
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'password' => [new Assert\NotBlank()],
        ]));
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => (string) $v->getPropertyPath(), 'message' => $v->getMessage()];
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
            ]);
        }

        $email = (string) $data['email'];
        $plain = (string) $data['password'];

        $user = $users->findOneBy(['email' => $email]);
        if (!$user || !$hasher->isPasswordValid($user, $plain)) {
            return new JsonResponse(['error' => 'invalid_credentials'], Response::HTTP_UNAUTHORIZED, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
            ]);
        }

        // Issue opaque tokens (access + refresh)
        $res = $issuer->issue($user, [], new \DateInterval('PT15M'), new \DateInterval('P30D'));

        return new JsonResponse([
            'access_token' => $res['access_token'],
            'access_expires_at' => $res['access_expires_at']->format(\DateTimeInterface::ATOM),
            'refresh_token' => $res['refresh_token'],
            'refresh_expires_at' => $res['refresh_expires_at']->format(\DateTimeInterface::ATOM),
            'token_type' => 'Bearer',
        ], Response::HTTP_OK, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    #[Route('/v1/auth/token/refresh', name: 'auth_refresh', methods: ['POST'])]
    public function refresh(Request $request, TokenRotator $rotator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $refresh = is_array($data) && isset($data['refresh_token']) ? (string) $data['refresh_token'] : '';
        if ('' === $refresh) {
            return new JsonResponse(
                ['error' => 'invalid_request', 'detail' => 'refresh_token is required'],
                Response::HTTP_BAD_REQUEST,
                [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Cache-Control' => 'no-store',
                    'Pragma' => 'no-cache',
                ]
            );
        }

        try {
            $res = $rotator->rotate(
                $refresh,
                new \DateInterval('PT15M'),
                new \DateInterval('P30D'),
            );
        } catch (\DomainException) {
            return new JsonResponse(['error' => 'invalid_refresh_token'], Response::HTTP_UNAUTHORIZED, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
            ]);
        }

        return new JsonResponse([
            'access_token' => $res['access_token'],
            'access_expires_at' => $res['access_expires_at']->format(\DateTimeInterface::ATOM),
            'refresh_token' => $res['refresh_token'],
            'refresh_expires_at' => $res['refresh_expires_at']->format(\DateTimeInterface::ATOM),
        ], 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    #[Route('/v1/auth/logout', name: 'auth_logout', methods: ['POST'])]
    public function logout(Request $request, TokenRevoker $revoker): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refresh = is_array($data) && isset($data['refresh_token']) ? (string) $data['refresh_token'] : '';
        if ('' === $refresh) {
            return new JsonResponse(
                ['error' => 'invalid_request', 'detail' => 'refresh_token is required'],
                Response::HTTP_BAD_REQUEST,
                [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Cache-Control' => 'no-store',
                    'Pragma' => 'no-cache',
                ]
            );
        }

        try {
            $status = $revoker->revokeByRefresh($refresh);
        } catch (\DomainException) {
            return new JsonResponse(['error' => 'invalid_refresh_token'], Response::HTTP_UNAUTHORIZED, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
            ]);
        }

        return new JsonResponse(['status' => $status], 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }
}
