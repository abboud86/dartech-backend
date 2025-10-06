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
     * Revoke a given refresh token chain.
     * Returns "revoked" or "already_revoked".
     *
     * @throws \DomainException when token is invalid/missing
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

        // Revoke this refresh
        $rt->setRevokedAt($now);

        // Revoke all ACTIVE access tokens of the same owner, comparing on FK id
        $ownerId = $rt->getOwner()?->getId();
        if ($ownerId instanceof \Symfony\Component\Uid\Ulid) {
            $ownerId = $ownerId->toRfc4122(); // convert ULID to uuid string for DB
        } else {
            $ownerId = (string) $ownerId;
        }

        $this->em->createQueryBuilder()
            ->update(AccessToken::class, 'at')
            ->set('at.revokedAt', ':now')
            ->where('IDENTITY(at.owner) = :ownerId')
            ->andWhere('at.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->execute();

        $this->em->flush();

        return 'revoked';
    }

    /**
     * Revoke all tokens (access + refresh) for the given user.
     * Returns counters + status.
     */
    public function revokeAllFor(User $owner): array
    {
        $now = new \DateTimeImmutable('now');

        $ownerId = $owner->getId();
        if ($ownerId instanceof \Symfony\Component\Uid\Ulid) {
            $ownerId = $ownerId->toRfc4122(); // convert ULID to uuid string for DB
        } else {
            $ownerId = (string) $ownerId;
        }

        $refreshUpdated = $this->em->createQueryBuilder()
            ->update(RefreshToken::class, 'rt')
            ->set('rt.revokedAt', ':now')
            ->where('IDENTITY(rt.owner) = :ownerId')
            ->andWhere('rt.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->execute();

        $accessUpdated = $this->em->createQueryBuilder()
            ->update(AccessToken::class, 'at')
            ->set('at.revokedAt', ':now')
            ->where('IDENTITY(at.owner) = :ownerId')
            ->andWhere('at.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->execute();

        return [
            'status' => ($refreshUpdated + $accessUpdated) > 0 ? 'revoked_all' : 'nothing_to_revoke',
            'refresh_revoked' => $refreshUpdated,
            'access_revoked' => $accessUpdated,
        ];
    }
}
