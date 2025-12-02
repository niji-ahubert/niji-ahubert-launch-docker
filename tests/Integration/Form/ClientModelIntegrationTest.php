<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form;

use App\Form\Model\ClientModel;
use App\Service\ClientNameNormalizer;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests d'intégration pour le modèle ClientModel.
 *
 * Ces tests vérifient l'intégration complète des validateurs
 * avec les services de l'application.
 */
class ClientModelIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;
    private ClientNameNormalizer $clientNameNormalizer;
    private FileSystemEnvironmentServices $environmentServices;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->validator = $container->get(ValidatorInterface::class);
        $this->clientNameNormalizer = $container->get(ClientNameNormalizer::class);
        $this->environmentServices = $container->get(FileSystemEnvironmentServices::class);
    }

    /**
     * Teste la validation complète avec un nom de client valide et unique.
     *
     * @return void
     */
    public function testValidClientModelWithUniqueNameIntegration(): void
    {
        $clientModel = new ClientModel();
        $uniqueName = 'client-test-' . uniqid();
        $clientModel->setClient($uniqueName);

        $violations = $this->validator->validate($clientModel);

        $this->assertCount(0, $violations, 'Un nom de client unique devrait être valide');
    }

    /**
     * Teste la normalisation d'un nom de client avec des caractères spéciaux.
     *
     * @return void
     */
    public function testClientNameNormalizationIntegration(): void
    {
        $originalName = 'Société Française & Co.';
        $normalizedName = $this->clientNameNormalizer->normalize($originalName);

        $this->assertSame('societe-francaise-co', $normalizedName);

        // Vérifier que le nom normalisé respecte les contraintes
        $clientModel = new ClientModel();
        $clientModel->setClient($normalizedName);

        $violations = $this->validator->validate($clientModel);

        // Il ne devrait y avoir aucune violation pour les contraintes de format
        $formatViolations = array_filter(
            iterator_to_array($violations),
            fn($violation) => !str_contains($violation->getMessage(), 'already_exists')
        );

        $this->assertEmpty($formatViolations, 'Le nom normalisé devrait respecter toutes les contraintes de format');
    }

    /**
     * Teste la validation avec différents types de noms invalides.
     *
     * @dataProvider invalidNamesProvider
     * @param string $invalidName
     * @param string $expectedMessageKey
     * @return void
     */
    public function testInvalidClientNamesIntegration(string $invalidName, string $expectedMessageKey): void
    {
        $clientModel = new ClientModel();
        $clientModel->setClient($invalidName);

        $violations = $this->validator->validate($clientModel);

        $this->assertGreaterThan(0, $violations->count(), "Le nom '$invalidName' devrait être invalide");

        // Vérifier qu'au moins une violation contient le message attendu
        $found = false;
        foreach ($violations as $violation) {
            if (str_contains($violation->getMessage(), $expectedMessageKey)) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Le message d'erreur '$expectedMessageKey' devrait être présent");
    }

    /**
     * Teste la génération de suggestions de noms.
     *
     * @return void
     */
    public function testClientNameSuggestionsIntegration(): void
    {
        $input = 'Ma Société Test';
        $suggestions = $this->clientNameNormalizer->generateAlternatives($input);

        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        // Vérifier que toutes les suggestions sont valides
        foreach ($suggestions as $suggestion) {
            $this->assertTrue(
                $this->clientNameNormalizer->isValid($suggestion),
                "La suggestion '$suggestion' devrait être valide"
            );
        }
    }

    /**
     * Fournit des noms invalides et leurs messages d'erreur attendus.
     *
     * @return array<array<string>>
     */
    public static function invalidNamesProvider(): array
    {
        return [
            ['', 'not_blank'],
            ['a', 'min_length'],
            [str_repeat('x', 51), 'max_length'],
            ['client test', 'invalid_characters'],
            ['CON', 'reserved_name'],
            ['.client', 'dot_not_allowed'],
            ['client.', 'dot_not_allowed'],
        ];
    }
}
