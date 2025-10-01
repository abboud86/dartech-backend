<?php

namespace App\Tests\Functional\Api;

use App\Entity\ArtisanProfile;
use App\Entity\User;
use App\Enum\KycStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MeEndpointTest extends WebTestCase
{
    public function testMeReturns401WithoutToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/me');

        $this->assertSame(401, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent() ?: '');
        $this->assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('unauthorized', $data['error']);
    }

    public function testMeReturns200WithTestAuthenticator(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        // Purge toute occurrence résiduelle de l'email de test (évite la contrainte d'unicité)
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :e')
            ->setParameter('e', 'me@example.test')
            ->execute();

        // Prépare les données
        $user = (new User())
            ->setEmail('me@example.test')
            ->setPassword('x'); // pas utilisé par l'authenticator de test

        $ap = (new ArtisanProfile())
            ->setUser($user)
            ->setDisplayName('Menuisier Ahmed')
            ->setPhone('+213555123456')
            ->setBio('Bio ok')
            ->setWilaya('Alger')
            ->setCommune('Bab Ezzouar')
            ->setKycStatus(KycStatus::PENDING);

        $em->persist($user);
        $em->persist($ap);
        $em->flush();

        // Appel authentifié via en-tête de test
        $client->request('GET', '/v1/me', server: [
            'HTTP_X_TEST_USER' => 'me@example.test',
        ]);

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode(), $response->getContent() ?: '');
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('me@example.test', $data['email']);
        $this->assertArrayHasKey('artisan_profile', $data);
        $this->assertSame('Menuisier Ahmed', $data['artisan_profile']['display_name']);
        $this->assertSame('+213555123456', $data['artisan_profile']['phone']);
        $this->assertSame('Alger', $data['artisan_profile']['wilaya']);
        $this->assertSame('Bab Ezzouar', $data['artisan_profile']['commune']);
        $this->assertSame('pending', $data['artisan_profile']['kyc_status']);
    }
}
