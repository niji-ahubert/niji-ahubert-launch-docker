<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ClientNameNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Tests unitaires pour le service ClientNameNormalizer.
 */
class ClientNameNormalizerTest extends TestCase
{
    private ClientNameNormalizer $normalizer;

    protected function setUp(): void
    {
        $slugger = new AsciiSlugger();
        $this->normalizer = new ClientNameNormalizer($slugger);
    }

    /**
     * Teste la normalisation de noms simples.
     *
     * @dataProvider simpleNamesProvider
     * @param string $input
     * @param string $expected
     * @return void
     */
    public function testNormalizeSimpleNames(string $input, string $expected): void
    {
        $result = $this->normalizer->normalize($input);
        $this->assertSame($expected, $result);
    }

    /**
     * Teste la normalisation de noms avec espaces.
     *
     * @return void
     */
    public function testNormalizeWithSpaces(): void
    {
        $result = $this->normalizer->normalize('Mon Client Test');
        $this->assertSame('mon-client-test', $result);
    }

    /**
     * Teste la normalisation de noms avec caractères spéciaux et accents.
     *
     * @return void
     */
    public function testNormalizeWithSpecialCharactersAndAccents(): void
    {
        $result = $this->normalizer->normalize('Société Française & Co.');
        $this->assertSame('societe-francaise-co', $result);
    }

    /**
     * Teste la normalisation de noms trop longs.
     *
     * @return void
     */
    public function testNormalizeLongName(): void
    {
        $longName = 'Un nom de client très très très très très très long qui dépasse la limite';
        $result = $this->normalizer->normalize($longName);
        
        $this->assertLessThanOrEqual(50, strlen($result));
        $this->assertStringStartsWith('un-nom-de-client-tres-tres-tres-tres-tres', $result);
    }

    /**
     * Teste la normalisation de noms vides.
     *
     * @return void
     */
    public function testNormalizeEmptyName(): void
    {
        $result = $this->normalizer->normalize('');
        
        $this->assertStringStartsWith('client-', $result);
        $this->assertGreaterThan(7, strlen($result)); // 'client-' + uniqid
    }

    /**
     * Teste la normalisation avec des caractères Unicode.
     *
     * @return void
     */
    public function testNormalizeUnicodeCharacters(): void
    {
        $result = $this->normalizer->normalize('Müller & Associés 中文');
        $this->assertSame('muller-associes', $result);
    }

    /**
     * Teste la méthode suggest avec différents types d'entrées.
     *
     * @dataProvider suggestProvider
     * @param string $input
     * @param string $expectedStart
     * @return void
     */
    public function testSuggest(string $input, string $expectedStart): void
    {
        $result = $this->normalizer->suggest($input);
        
        $this->assertStringStartsWith($expectedStart, $result);
        $this->assertLessThanOrEqual(50, strlen($result));
    }

    /**
     * Teste la validation de noms valides.
     *
     * @dataProvider validNamesProvider
     * @param string $validName
     * @return void
     */
    public function testIsValidWithValidNames(string $validName): void
    {
        $this->assertTrue($this->normalizer->isValid($validName));
    }

    /**
     * Teste la validation de noms invalides.
     *
     * @dataProvider invalidNamesProvider
     * @param string $invalidName
     * @return void
     */
    public function testIsValidWithInvalidNames(string $invalidName): void
    {
        $this->assertFalse($this->normalizer->isValid($invalidName));
    }

    /**
     * Teste la génération d'alternatives.
     *
     * @return void
     */
    public function testGenerateAlternatives(): void
    {
        $alternatives = $this->normalizer->generateAlternatives('Mon Client');
        
        $this->assertIsArray($alternatives);
        $this->assertNotEmpty($alternatives);
        $this->assertContains('mon-client-1', $alternatives);
        $this->assertContains('mon-client-2', $alternatives);
        
        // Vérifier qu'il y a une alternative avec l'année
        $year = date('Y');
        $this->assertContains('mon-client-' . $year, $alternatives);
    }

    /**
     * Fournit des noms simples pour les tests de normalisation.
     *
     * @return array<array<string>>
     */
    public static function simpleNamesProvider(): array
    {
        return [
            ['client', 'client'],
            ['Client', 'client'],
            ['CLIENT', 'client'],
            ['client-test', 'client-test'],
            ['client_test', 'client-test'], // Les underscores sont remplacés par des tirets
            ['123client', '123client'],
            ['  client  ', 'client'],
        ];
    }

    /**
     * Fournit des données pour les tests de suggestion.
     *
     * @return array<array<string>>
     */
    public static function suggestProvider(): array
    {
        return [
            ['Société Française SARL', 'societe-francaise'],
            ['Microsoft Corporation Inc.', 'microsoft-corporation'],
            ['Jean-Pierre & Associés', 'jean-pierre-associes'],
            ['ABC Company 2024', 'abc-company-2024'],
            ['Test!@#$%Client', 'test-client'],
        ];
    }

    /**
     * Fournit des noms valides pour les tests de validation.
     *
     * @return array<array<string>>
     */
    public static function validNamesProvider(): array
    {
        return [
            ['client'],
            ['Client-Test'],
            ['client_123'],
            ['mon-client-2024'],
            ['ABC'],
            ['test-client'],
        ];
    }

    /**
     * Fournit des noms invalides pour les tests de validation.
     *
     * @return array<array<string>>
     */
    public static function invalidNamesProvider(): array
    {
        return [
            ['a'], // Trop court
            [str_repeat('A', 51)], // Trop long
            ['client test'], // Espace
            ['client@test'], // Caractère spécial
            ['CON'], // Nom réservé
            ['PRN'], // Nom réservé
            ['.client'], // Commence par un point
            ['client.'], // Finit par un point
            [''], // Vide
        ];
    }
}
