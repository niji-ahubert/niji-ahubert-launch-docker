<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\ValidFolderName;
use App\Validator\Constraints\ValidFolderNameValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests unitaires pour le validateur ValidFolderNameValidator.
 */
class ValidFolderNameValidatorTest extends TestCase
{
    private ValidFolderNameValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new ValidFolderNameValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        
        $this->validator->initialize($this->context);
    }

    /**
     * Teste la validation avec des noms valides.
     *
     * @dataProvider validNamesProvider
     * @param string $validName
     * @return void
     */
    public function testValidNames(string $validName): void
    {
        $constraint = new ValidFolderName();
        
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($validName, $constraint);
    }

    /**
     * Teste la validation avec des caractères interdits.
     *
     * @dataProvider invalidCharactersProvider
     * @param string $invalidName
     * @return void
     */
    public function testInvalidCharacters(string $invalidName): void
    {
        $constraint = new ValidFolderName();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');
            
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->invalidCharactersMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($invalidName, $constraint);
    }

    /**
     * Teste la validation avec des noms réservés.
     *
     * @dataProvider reservedNamesProvider
     * @param string $reservedName
     * @return void
     */
    public function testReservedNames(string $reservedName): void
    {
        $constraint = new ValidFolderName();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');
            
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->reservedNameMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($reservedName, $constraint);
    }

    /**
     * Teste la validation avec des noms commençant ou finissant par un point.
     *
     * @dataProvider dotNamesProvider
     * @param string $dotName
     * @return void
     */
    public function testDotNames(string $dotName): void
    {
        $constraint = new ValidFolderName();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');
            
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->dotMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($dotName, $constraint);
    }

    /**
     * Teste la validation avec des valeurs nulles ou vides.
     *
     * @return void
     */
    public function testNullAndEmptyValues(): void
    {
        $constraint = new ValidFolderName();
        
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
        $this->validator->validate('', $constraint);
    }

    /**
     * Fournit des noms valides pour les tests.
     *
     * @return array<array<string>>
     */
    public static function validNamesProvider(): array
    {
        return [
            ['client1'],
            ['mon-client'],
            ['client_test'],
            ['Client-2024'],
            ['test123'],
            ['a'],
            ['ABC'],
        ];
    }

    /**
     * Fournit des noms avec caractères invalides pour les tests.
     *
     * @return array<array<string>>
     */
    public static function invalidCharactersProvider(): array
    {
        return [
            ['client<test'],
            ['client>test'],
            ['client:test'],
            ['client"test'],
            ['client/test'],
            ['client\\test'],
            ['client|test'],
            ['client?test'],
            ['client*test'],
            ['client test'], // espace
            ['client@test'],
            ['client#test'],
        ];
    }

    /**
     * Fournit des noms réservés pour les tests.
     *
     * @return array<array<string>>
     */
    public static function reservedNamesProvider(): array
    {
        return [
            ['CON'],
            ['PRN'],
            ['AUX'],
            ['NUL'],
            ['COM1'],
            ['COM9'],
            ['LPT1'],
            ['LPT9'],
            ['con'], // minuscule
            ['Con'], // mixte
            ['CON.txt'], // avec extension
        ];
    }

    /**
     * Fournit des noms avec points pour les tests.
     *
     * @return array<array<string>>
     */
    public static function dotNamesProvider(): array
    {
        return [
            ['.client'],
            ['client.'],
            ['.client.'],
            ['..client'],
            ['client..'],
        ];
    }
}
