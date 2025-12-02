<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Model;

use App\Form\Model\ClientModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests unitaires pour le modèle ClientModel.
 */
class ClientModelTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->addDefaultDoctrineAnnotationReader()
            ->getValidator();
    }

    /**
     * Teste la création d'un modèle client valide.
     *
     * @return void
     */
    public function testValidClientModel(): void
    {
        $clientModel = new ClientModel();
        $clientModel->setClient('Mon Client Test');

        $violations = $this->validator->validate($clientModel);

        $this->assertCount(0, $violations);
        $this->assertSame('Mon Client Test', $clientModel->getClient());
    }

    /**
     * Teste la validation avec un nom de client vide.
     *
     * @return void
     */
    public function testEmptyClientName(): void
    {
        $clientModel = new ClientModel();
        $clientModel->setClient('');

        $violations = $this->validator->validate($clientModel);

        $this->assertCount(1, $violations);
        $this->assertSame('client.validation.name.not_blank', $violations[0]->getMessage());
    }

    /**
     * Teste la validation avec un nom de client trop court.
     *
     * @return void
     */
    public function testTooShortClientName(): void
    {
        $clientModel = new ClientModel();
        $clientModel->setClient('A');

        $violations = $this->validator->validate($clientModel);

        $this->assertCount(1, $violations);
        $this->assertSame('client.validation.name.min_length', $violations[0]->getMessage());
    }

    /**
     * Teste la validation avec un nom de client trop long.
     *
     * @return void
     */
    public function testTooLongClientName(): void
    {
        $clientModel = new ClientModel();
        $clientModel->setClient(str_repeat('A', 51)); // Plus de 50 caractères

        $violations = $this->validator->validate($clientModel);

        $this->assertCount(1, $violations);
        $this->assertSame('client.validation.name.max_length', $violations[0]->getMessage());
    }

    /**
     * Teste la validation avec des caractères invalides.
     *
     * @return void
     */
    public function testInvalidCharacters(): void
    {
        $clientModel = new ClientModel();
        $clientModel->setClient('client test'); // Espace non autorisé

        $violations = $this->validator->validate($clientModel);

        $this->assertCount(1, $violations);
        $this->assertSame('client.validation.name.invalid_characters', $violations[0]->getMessage());
    }

    /**
     * Teste la validation avec un nom réservé.
     *
     * @return void
     */
    public function testReservedName(): void
    {
        $clientModel = new ClientModel();
        $clientModel->setClient('CON'); // Nom réservé Windows

        $violations = $this->validator->validate($clientModel);

        $this->assertCount(1, $violations);
        $this->assertSame('client.validation.name.reserved_name', $violations[0]->getMessage());
    }

    /**
     * Teste la validation avec un nom commençant par un point.
     *
     * @return void
     */
    public function testNameStartingWithDot(): void
    {
        $clientModel = new ClientModel();
        $clientModel->setClient('.client');

        $violations = $this->validator->validate($clientModel);

        $this->assertCount(1, $violations);
        $this->assertSame('client.validation.name.dot_not_allowed', $violations[0]->getMessage());
    }

    /**
     * Teste la validation avec un nom de client existant.
     * 
     * Note: Ce test nécessite un environnement de test avec des mocks
     * pour simuler l'existence de clients.
     *
     * @return void
     */
    public function testExistingClientName(): void
    {
        // Ce test nécessiterait des mocks pour EnvironmentServices
        // et ClientNameNormalizer pour simuler un client existant.
        // Il est préférable de tester cette fonctionnalité dans les tests d'intégration.
        $this->markTestSkipped('Test nécessitant des mocks complexes, couvert par les tests d\'intégration.');
    }

    /**
     * Teste la méthode fluide setClient.
     *
     * @return void
     */
    public function testFluentInterface(): void
    {
        $clientModel = new ClientModel();
        $result = $clientModel->setClient('Test Client');

        $this->assertSame($clientModel, $result);
        $this->assertSame('Test Client', $clientModel->getClient());
    }
}
