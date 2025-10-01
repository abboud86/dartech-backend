<?php

namespace App\Controller;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AuthController extends AbstractController
{
    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }

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
        'id'    => (string) $user->getId(),
        'email' => $user->getEmail(),
    ], Response::HTTP_CREATED);
    }

}
