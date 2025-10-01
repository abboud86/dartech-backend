<?php

namespace App\Tests\Unit\Validation;

use App\Entity\ArtisanProfile;
use App\Entity\User;
use App\Enum\KycStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArtisanProfileValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);

        /** @var TranslatorInterface $t */
        $t = static::getContainer()->get(TranslatorInterface::class);
        $t->setLocale('fr'); // messages FR
    }

    private function validUser(): User
    {
        // User minimal pour la relation (non persisté, pas nécessaire pour la validation)
        return (new User())
            ->setEmail('valid@example.test')
            ->setPassword('x');
    }

    private function validProfile(): ArtisanProfile
    {
        return (new ArtisanProfile())
            ->setUser($this->validUser())
            ->setDisplayName('Menuisier Ahmed')
            ->setPhone('+213555123456')
            ->setBio('Bio OK')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar')
            ->setKycStatus(KycStatus::PENDING);
    }

    public function testDisplayNameRequiredAndLength(): void
    {
        $ap = $this->validProfile();
        $ap->setDisplayName(''); // NotBlank
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertSame('Le nom d’affichage est requis.', $viol[0]->getMessage());

        $ap = $this->validProfile();
        $ap->setDisplayName(str_repeat('a', 81)); // >80
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertStringContainsString('Nom d’affichage trop long', $viol[0]->getMessage());
    }

    public function testPhoneRequiredFormatE164AndLength(): void
    {
        $ap = $this->validProfile();
        $ap->setPhone(''); // NotBlank
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertSame('Le numéro de téléphone est requis.', $viol[0]->getMessage());

        $ap = $this->validProfile();
        $ap->setPhone('0550-123-456'); // mauvais format
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertSame('Numéro de téléphone invalide (format E.164, ex: +213555123456).', $viol[0]->getMessage());

        $ap = $this->validProfile();
        $ap->setPhone('+'.str_repeat('1', 20)); // >20
        $viol = $this->validator->validate($ap, null, ['create']);
        $messages = array_map(fn ($v) => $v->getMessage(), iterator_to_array($viol));
        $this->assertTrue(
            (bool) array_filter($messages, fn ($m) => str_contains($m, 'Téléphone trop long')),
            'Le message "Téléphone trop long" doit apparaître parmi les violations: '.implode(' | ', $messages)
        );
    }

    public function testBioMaxLength(): void
    {
        $ap = $this->validProfile();
        $ap->setBio(str_repeat('b', 501)); // >500
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertStringContainsString('La bio est trop longue', $viol[0]->getMessage());
    }

    public function testWilayaRequiredAndLength(): void
    {
        $ap = $this->validProfile();
        $ap->setWilaya(''); // NotBlank
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertSame('La wilaya est requise.', $viol[0]->getMessage());

        $ap = $this->validProfile();
        $ap->setWilaya(str_repeat('w', 65)); // >64
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertStringContainsString('Wilaya trop longue', $viol[0]->getMessage());
    }

    public function testCommuneRequiredAndLength(): void
    {
        $ap = $this->validProfile();
        $ap->setCommune(''); // NotBlank
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertSame('La commune est requise.', $viol[0]->getMessage());

        $ap = $this->validProfile();
        $ap->setCommune(str_repeat('c', 65)); // >64
        $viol = $this->validator->validate($ap, null, ['create']);
        $this->assertStringContainsString('Commune trop longue', $viol[0]->getMessage());
    }
}
