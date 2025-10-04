<?php

namespace App\Tests\Functional\Api;

use App\Entity\ArtisanProfile;
use App\Entity\User;
use App\Enum\KycStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MeEndpointExtraTest extends WebTestCase
{
    public function testMeReturns403WhenNoProfile(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', 'noprof@example.test')
            ->execute();

        $user = (new User())->setEmail('noprof@example.test')->setPassword('x');
        $em->persist($user);
        $em->flush();

        $client->request('GET', '/v1/me', server: ['HTTP_X_TEST_USER' => 'noprof@example.test']);

        $res = $client->getResponse();
        self::assertSame(403, $res->getStatusCode(), $res->getContent() ?: '');
        $data = json_decode($res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('forbidden', $data['error'] ?? null);
        self::assertSame('profile_required', $data['detail'] ?? null);
    }

    public function testMeRateLimitedReturns429(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', 'ratelimit@example.test')
            ->execute();

        $user = (new User())->setEmail('ratelimit@example.test')->setPassword('x');

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Rate Limited User')
            ->setPhone('+213555000000')
            ->setBio('RL ok')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar')
            ->setKycStatus(KycStatus::PENDING);

        $em->persist($user);
        $em->persist($ap);
        $em->flush();

        // 1re et 2e requêtes OK
        $client->request('GET', '/v1/me', server: ['HTTP_X_TEST_USER' => 'ratelimit@example.test']);
        $client->request('GET', '/v1/me', server: ['HTTP_X_TEST_USER' => 'ratelimit@example.test']);

        // 3e = 429
        $client->request('GET', '/v1/me', server: ['HTTP_X_TEST_USER' => 'ratelimit@example.test']);
        $res = $client->getResponse();

        self::assertSame(429, $res->getStatusCode(), $res->getContent() ?: '');
        $data = json_decode($res->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('too_many_requests', $data['error'] ?? null);
    }
}
