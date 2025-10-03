<?php

namespace App\Security;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;

final class TokenRevoker
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @return 'revoked'|'already_revoked'
     */
    public function revokeByRefresh(string $refreshPlain): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $hash = hash('sha256', $refreshPlain);

        /** @var RefreshToken|null $rt */
        $rt = $this->em->getRepository(RefreshToken::class)->findOneBy(['tokenHash' => $hash]);
        if (!$rt) {
            throw new \DomainException('invalid_refresh_token');
        }

        if (null !== $rt->getRevokedAt()) {
            return 'already_revoked';
        }

        $rt->setRevokedAt($now);
        $this->em->flush();

        return 'revoked';
    }
}
