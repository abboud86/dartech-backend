<?php

namespace App\Security;

use App\Entity\AccessToken;
use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class TokenRotator
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @return array{
     *   owner: User,
     *   access_token: string,
     *   access_expires_at: \DateTimeImmutable,
     *   refresh_token: string,
     *   refresh_expires_at: \DateTimeImmutable
     * }
     */
    public function rotate(string $refreshPlain, \DateInterval $accessTtl, \DateInterval $refreshTtl): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $hash = hash('sha256', $refreshPlain);

        /** @var RefreshToken|null $old */
        $old = $this->em->getRepository(RefreshToken::class)->findOneBy(['tokenHash' => $hash]);
        if (!$old) {
            throw new \DomainException('invalid_refresh_token'); // 401
        }
        if (null !== $old->getRevokedAt()) {
            throw new \DomainException('refresh_token_revoked'); // 401
        }
        if ($old->getExpiresAt() <= $now) {
            throw new \DomainException('refresh_token_expired'); // 401
        }

        $owner = $old->getOwner();

        // révoquer l’ancien refresh
        $old->setRevokedAt($now);

        // nouveaux tokens (opaques côté client, hashés en DB)
        $accessPlain = $this->base64url(random_bytes(32));
        $refreshPlain = $this->base64url(random_bytes(32));

        $accessHash = hash('sha256', $accessPlain);
        $refreshHash = hash('sha256', $refreshPlain);

        $at = (new AccessToken())
            ->setTokenHash($accessHash)
            ->setOwner($owner)
            ->setScopes([]) // à enrichir selon besoin
            ->setCreatedAt($now)
            ->setLastUsedAt($now)
            ->setExpiresAt($now->add($accessTtl));

        $rt = (new RefreshToken())
            ->setTokenHash($refreshHash)
            ->setOwner($owner)
            ->setCreatedAt($now)
            ->setExpiresAt($now->add($refreshTtl))
            ->setRotatedFrom($old);

        $this->em->persist($at);
        $this->em->persist($rt);
        $this->em->flush();

        return [
            'owner' => $owner,
            'access_token' => $accessPlain,
            'access_expires_at' => $at->getExpiresAt(),
            'refresh_token' => $refreshPlain,
            'refresh_expires_at' => $rt->getExpiresAt(),
        ];
    }

    private function base64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
