<?php

namespace App\Tests\Unit\Validation;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var TranslatorInterface $t */
        $t = static::getContainer()->get(TranslatorInterface::class);
        $t->setLocale('fr');
        $this->validator = static::getContainer()->get(ValidatorInterface::class); // 555
    }

    public function testEmailNotBlankAndFormatAndLength(): void
    {
        $u = new User();
        // email null -> NotBlank
        $violations = $this->validator->validate($u, null, ['create']);
        $this->assertSame(1, $violations->count());
        $this->assertSame('L’email est requis.', $violations[0]->getMessage());

        // email invalide
        $u->setEmail('not-an-email');
        $violations = $this->validator->validate($u, null, ['create']);
        $this->assertSame('Adresse email invalide.', $violations[0]->getMessage());

        // email trop long (181 chars avant @)
        $long = str_repeat('a', 181).'@example.test';
        $u->setEmail($long);
        $violations = $this->validator->validate($u, null, ['create']);
        $this->assertStringContainsString('Email trop long', $violations[0]->getMessage());
    }

    public function testEmailUniqueEntity(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        // Purge ciblée pour éviter les collisions de runs précédents
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', 'dup@example.test')
            ->execute();

        // 1er utilisateur
        $u1 = (new User())->setEmail('dup@example.test')->setPassword('x');
        $em->persist($u1);
        $em->flush();

        // 2e utilisateur (même email) : on ne flush PAS – on valide seulement
        $u2 = (new User())->setEmail('dup@example.test')->setPassword('x');
        $violations = $this->validator->validate($u2, null, ['create']);

        $this->assertGreaterThanOrEqual(1, $violations->count());
        $messages = array_map(fn ($v) => $v->getMessage(), iterator_to_array($violations));
        $this->assertContains('Cet email est déjà utilisé.', $messages);
    }
}
