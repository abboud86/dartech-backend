<?php

namespace App\Security;

use App\Entity\AccessToken;
use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class TokenRevoker
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Révoque un refresh_token donné (chaîné).
     * Retourne "revoked" ou "already_revoked".
     *
     * @throws \DomainException si le token n'existe pas / est invalide
     */
    public function revokeByRefresh(string $refreshPlain): string
    {
        $hash = hash('sha256', $refreshPlain);

        /** @var RefreshToken|null $rt */
        $rt = $this->em->getRepository(RefreshToken::class)->findOneBy(['tokenHash' => $hash]);
        if (!$rt) {
            throw new \DomainException('invalid_refresh_token');
        }

        $now = new \DateTimeImmutable('now');

        if (null !== $rt->getRevokedAt()) {
            return 'already_revoked';
        }

        // Révoque ce refresh
        $rt->setRevokedAt($now);

        // Révoque les access du même owner via son EMAIL (évite les problèmes ULID/UUID)
        $ownerEmail = $rt->getOwner()->getEmail();

        $this->em->createQueryBuilder()
            ->update(AccessToken::class, 'at')
            ->set('at.revokedAt', ':now')
            ->where('at.owner IN (SELECT u FROM '.User::class.' u WHERE u.email = :email)')
            ->andWhere('at.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('email', $ownerEmail)
            ->getQuery()
            ->execute();

        $this->em->flush();

        return 'revoked';
    }

    /**
     * Révoque tous les tokens (access + refresh) d'un utilisateur.
     * Retourne les compteurs et statut. Filtrage par EMAIL (pas d'id en paramètre DB).
     */
    public function revokeAllFor(User $owner): array
    {
        $now = new \DateTimeImmutable('now');
        $email = $owner->getEmail();

        $refreshUpdated = $this->em->createQueryBuilder()
            ->update(RefreshToken::class, 'rt')
            ->set('rt.revokedAt', ':now')
            ->where('rt.owner IN (SELECT u FROM '.User::class.' u WHERE u.email = :email)')
            ->andWhere('rt.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('email', $email)
            ->getQuery()
            ->execute();

        $accessUpdated = $this->em->createQueryBuilder()
            ->update(AccessToken::class, 'at')
            ->set('at.revokedAt', ':now')
            ->where('at.owner IN (SELECT u FROM '.User::class.' u WHERE u.email = :email)')
            ->andWhere('at.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('email', $email)
            ->getQuery()
            ->execute();

        return [
            'status' => ($refreshUpdated + $accessUpdated) > 0 ? 'revoked_all' : 'nothing_to_revoke',
            'refresh_revoked' => $refreshUpdated,
            'access_revoked' => $accessUpdated,
        ];
    }
}
