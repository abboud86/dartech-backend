<?php

namespace App\Security;

use App\Entity\AccessToken;
use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class TokenIssuer
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Émet un couple access/refresh.
     *
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

        // Valeurs opaques (non devinables)
        $accessPlain = $this->base64url(random_bytes(32));
        $refreshPlain = $this->base64url(random_bytes(32));

        // Hashs persistés (hex 64)
        $accessHash = hash('sha256', $accessPlain);
        $refreshHash = hash('sha256', $refreshPlain);

        // AccessToken
        $at = (new AccessToken())
            ->setOwner($owner)
            ->setScopes($scopes)
            ->setTokenHash($accessHash)
            ->setCreatedAt($now)
            ->setLastUsedAt($now)              // 👈 important
            ->setExpiresAt($now->add($accessTtl));

        // RefreshToken
        $rt = (new RefreshToken())
            ->setOwner($owner)
            ->setTokenHash($refreshHash)
            ->setCreatedAt($now)
            ->setExpiresAt($now->add($refreshTtl));

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

    /**
     * Alias utile si certains appels existants attendent issueForUser().
     */
    public function issueForUser(User $user, \DateInterval $accessTtl, \DateInterval $refreshTtl): array
    {
        return $this->issue($user, [], $accessTtl, $refreshTtl);
    }

    private function base64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
