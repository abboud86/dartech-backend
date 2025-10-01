<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Entity\ArtisanProfile;
use App\Enum\KycStatus;
use App\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class KycStatusTransitionTest extends TestCase
{
    private function makeProfile(KycStatus $status): ArtisanProfile
    {
        $ap = new ArtisanProfile();
        $ap
            ->setDisplayName('Menuisier Ahmed')
            ->setPhone('+213555123456')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar')
            ->setKycStatus($status);

        return $ap;
    }

    public function testTransitionsValides(): void
    {
        // pending -> verified
        $ap = $this->makeProfile(KycStatus::PENDING);
        $ap->changeKycStatus(KycStatus::VERIFIED);
        $this->assertSame(KycStatus::VERIFIED, $ap->getKycStatus());

        // pending -> rejected
        $ap = $this->makeProfile(KycStatus::PENDING);
        $ap->changeKycStatus(KycStatus::REJECTED);
        $this->assertSame(KycStatus::REJECTED, $ap->getKycStatus());

        // rejected -> pending (resoumission)
        $ap = $this->makeProfile(KycStatus::REJECTED);
        $ap->changeKycStatus(KycStatus::PENDING);
        $this->assertSame(KycStatus::PENDING, $ap->getKycStatus());
    }

    public function testTransitionsInterdites(): void
    {
        // verified -> pending
        $ap = $this->makeProfile(KycStatus::VERIFIED);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Transition KYC interdite: verified → pending');
        $ap->changeKycStatus(KycStatus::PENDING);

        // verified -> rejected
        $ap = $this->makeProfile(KycStatus::VERIFIED);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Transition KYC interdite: verified → rejected');
        $ap->changeKycStatus(KycStatus::REJECTED);

        // rejected -> verified
        $ap = $this->makeProfile(KycStatus::REJECTED);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Transition KYC interdite: rejected → verified');
        $ap->changeKycStatus(KycStatus::VERIFIED);
    }
}
