<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\TokenRevoker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LogoutAllController extends AbstractController
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/v1/auth/logout/all', name: 'auth_logout_all', methods: ['POST'])]
    public function __invoke(Request $request, TokenRevoker $revoker): JsonResponse
    {
        $user = $this->getUser();

        // Allow dev smoke: if test-auth flag is on and X-TEST-USER is provided,
        // map it to a real User entity (create if missing).
        if (!$user instanceof User) {
            $allow = (($_ENV['APP_ENABLE_TEST_AUTH'] ?? '0') === '1');
            $email = $request->headers->get('X-TEST-USER');
            if ($allow && is_string($email) && '' !== $email) {
                $entity = $this->users->findOneBy(['email' => $email]);
                if (!$entity) {
                    $entity = (new User())->setEmail($email)->setPassword('x');
                    $this->em->persist($entity);
                    $this->em->flush();
                }
                $user = $entity;
            }
        }

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $result = $revoker->revokeAllFor($user);

        return new JsonResponse([
            'status' => $result['status'],
            'access_revoked' => $result['access_revoked'],
            'refresh_revoked' => $result['refresh_revoked'],
        ], Response::HTTP_OK, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
