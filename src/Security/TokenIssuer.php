<?php

namespace App\Security;

use App\Entity\AccessToken;
use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class TokenIssuer
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param list<string> $scopes
     *
     * @return array{
     *   access_token: string,
     *   access_expires_at: \DateTimeImmutable,
     *   refresh_token: string,
     *   refresh_expires_at: \DateTimeImmutable
     * }
     */
    public function issue(User $owner, array $scopes, \DateInterval $accessTtl, \DateInterval $refreshTtl): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        // Génère des valeurs opaques côté client
        $accessPlain = $this->base64url(random_bytes(32));
        $refreshPlain = $this->base64url(random_bytes(32));

        // Hash à persister (hex 64)
        $accessHash = hash('sha256', $accessPlain);
        $refreshHash = hash('sha256', $refreshPlain);

        // AccessToken
        $at = new AccessToken();
        $at->setTokenHash($accessHash);
        $at->setOwner($owner);
        $at->setScopes($scopes);
        $at->setCreatedAt($now);
        $at->setExpiresAt($now->add($accessTtl));

        // RefreshToken
        $rt = new RefreshToken();
        $rt->setTokenHash($refreshHash);
        $rt->setOwner($owner);
        $rt->setCreatedAt($now);
        $rt->setExpiresAt($now->add($refreshTtl));

        $this->em->persist($at);
        $this->em->persist($rt);
        $this->em->flush();

        return [
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
