<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour le contrôleur ClientController.
 */
class ClientControllerTest extends WebTestCase
{
    /**
     * Teste l'affichage du formulaire d'ajout de client.
     *
     * @return void
     */
    public function testAddClientFormDisplay(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/client/add');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Ajouter un nouveau client');
        $this->assertSelectorExists('form[name="client"]');
        $this->assertSelectorExists('input[name="client[client]"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    /**
     * Teste la soumission du formulaire avec des données valides.
     *
     * @return void
     */
    public function testAddClientFormSubmissionWithValidData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/client/add');

        $form = $crawler->selectButton('Créer le client')->form([
            'client[client]' => 'Mon Nouveau Client',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/client/');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Le client "Mon Nouveau Client" a été créé avec succès.');
    }

    /**
     * Teste la soumission du formulaire avec des données invalides.
     *
     * @return void
     */
    public function testAddClientFormSubmissionWithInvalidData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/client/add');

        $form = $crawler->selectButton('Créer le client')->form([
            'client[client]' => '', // Nom vide
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.invalid-feedback');
        $this->assertSelectorTextContains('.invalid-feedback', 'Le nom du client ne peut pas être vide.');
    }

    /**
     * Teste la soumission du formulaire avec un nom trop court.
     *
     * @return void
     */
    public function testAddClientFormSubmissionWithTooShortName(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/client/add');

        $form = $crawler->selectButton('Créer le client')->form([
            'client[client]' => 'A', // Nom trop court
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.invalid-feedback');
        $this->assertSelectorTextContains('.invalid-feedback', 'au moins 2 caractères');
    }

    /**
     * Teste le lien de retour vers la liste des clients.
     *
     * @return void
     */
    public function testBackToListLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/client/add');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/client/"]');
        $this->assertSelectorTextContains('a[href="/client/"]', 'Retour à la liste');
    }

    /**
     * Teste l'endpoint de suggestion de noms de clients.
     *
     * @return void
     */
    public function testClientSuggestionEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/client/suggest', ['q' => 'Société Française']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('suggestions', $response);
        $this->assertArrayHasKey('normalized', $response);
        $this->assertIsArray($response['suggestions']);
        $this->assertNotEmpty($response['suggestions']);
        $this->assertStringContains('societe-francaise', $response['normalized']);
    }

    /**
     * Teste l'endpoint de suggestion avec une requête trop courte.
     *
     * @return void
     */
    public function testClientSuggestionWithShortQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/client/suggest', ['q' => 'A']);

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEmpty($response);
    }

    /**
     * Teste l'endpoint de suggestion sans paramètre.
     *
     * @return void
     */
    public function testClientSuggestionWithoutQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/client/suggest');

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEmpty($response);
    }

    /**
     * Teste l'endpoint de vérification de disponibilité avec un nom disponible.
     *
     * @return void
     */
    public function testCheckAvailabilityWithAvailableName(): void
    {
        $client = static::createClient();
        $uniqueName = 'client-test-' . uniqid();
        $client->request('GET', '/client/check-availability', ['name' => $uniqueName]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('available', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('normalized', $response);
        $this->assertTrue($response['available']);
    }

    /**
     * Teste l'endpoint de vérification de disponibilité sans nom.
     *
     * @return void
     */
    public function testCheckAvailabilityWithoutName(): void
    {
        $client = static::createClient();
        $client->request('GET', '/client/check-availability');

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('available', $response);
        $this->assertFalse($response['available']);
        $this->assertSame('Nom vide', $response['message']);
    }

    /**
     * Teste l'endpoint de vérification de disponibilité avec un nom vide.
     *
     * @return void
     */
    public function testCheckAvailabilityWithEmptyName(): void
    {
        $client = static::createClient();
        $client->request('GET', '/client/check-availability', ['name' => '']);

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('available', $response);
        $this->assertFalse($response['available']);
        $this->assertSame('Nom vide', $response['message']);
    }
}
