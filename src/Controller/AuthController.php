<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
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
    public function registerPlaceholder(): JsonResponse
    {
        return new JsonResponse(['status' => 'not_implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/v1/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        // Validation d'entrée (doc Validator) – contrôle léger côté contrôleur
        $violations = $validator->validate($data, new Assert\Collection([
            'fields' => [
                'email' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
                'password' => [new Assert\NotBlank(), new Assert\Length(min: 8)],
            ],
            'allowExtraFields' => true,
            'allowMissingFields' => false,
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

        // Conflit si email déjà utilisé (409)
        if (null !== $users->findOneBy(['email' => $email])) {
            return new JsonResponse(['error' => 'email_already_exists'], Response::HTTP_CONFLICT);
        }

        // Création user + hash (hasher "auto" configuré dans security.yaml)
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
    public function loginPlaceholder(): JsonResponse
    {
        return new JsonResponse(['status' => 'not_implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/v1/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        // Validation d'entrée
        $violations = $validator->validate($data, new Assert\Collection([
            'fields' => [
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'password' => [new Assert\NotBlank()],
            ],
            'allowExtraFields' => true,
            'allowMissingFields' => false,
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

        $user = $users->findOneBy(['email' => $email]);
        if (!$user || !$hasher->isPasswordValid($user, $plain)) {
            return new JsonResponse(['error' => 'invalid_credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // IMPORTANT : on renvoie le token que ton authenticator attend (dev-mode)
        $access = $_ENV['API_DEV_TOKEN'] ?? 'dev-token';

        return new JsonResponse([
            'access_token' => $access,
            'token_type' => 'Bearer',
            // (pas de refresh ici : ajouté en P2-02.2 avec vraie persistance des tokens)
        ], Response::HTTP_OK);
    }

    #[Route('/v1/auth/token/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refreshPlaceholder(): JsonResponse
    {
        return new JsonResponse(
            ['status' => 'not_implemented'],
            Response::HTTP_NOT_IMPLEMENTED
        );
    }
}
