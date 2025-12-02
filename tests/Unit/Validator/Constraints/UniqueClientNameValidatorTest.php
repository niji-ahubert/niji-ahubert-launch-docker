<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator\Constraints;

use App\Service\ClientNameNormalizer;
use App\Services\FileSystemEnvironmentServices;
use App\Validator\Constraints\UniqueClientName;
use App\Validator\Constraints\UniqueClientNameValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests unitaires pour le validateur UniqueClientNameValidator.
 */
class UniqueClientNameValidatorTest extends TestCase
{
    private UniqueClientNameValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;
    private ClientNameNormalizer $clientNameNormalizer;
    private FileSystemEnvironmentServices $environmentServices;

    protected function setUp(): void
    {
        $this->clientNameNormalizer = $this->createMock(ClientNameNormalizer::class);
        $this->environmentServices = $this->createMock(FileSystemEnvironmentServices::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->validator = new UniqueClientNameValidator(
            $this->clientNameNormalizer,
            $this->environmentServices
        );

        $this->validator->initialize($this->context);
    }

    /**
     * Teste la validation avec un nom de client unique.
     *
     * @return void
     */
    public function testValidateWithUniqueClientName(): void
    {
        $constraint = new UniqueClientName();
        $clientName = 'nouveau-client';

        $this->clientNameNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($clientName)
            ->willReturn('nouveau-client');

        $this->environmentServices
            ->expects($this->once())
            ->method('getFolder')
            ->with(FileSystemEnvironmentServices::PROJECT_IN_GENERATOR_ROOT_DIRECTORY)
            ->willReturn(['client-existant', 'autre-client']);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($clientName, $constraint);
    }

    /**
     * Teste la validation avec un nom de client existant.
     *
     * @return void
     */
    public function testValidateWithExistingClientName(): void
    {
        $constraint = new UniqueClientName();
        $clientName = 'Client Existant';
        $normalizedName = 'client-existant';

        $this->clientNameNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($clientName)
            ->willReturn($normalizedName);

        $this->environmentServices
            ->expects($this->once())
            ->method('getFolder')
            ->with(FileSystemEnvironmentServices::PROJECT_IN_GENERATOR_ROOT_DIRECTORY)
            ->willReturn(['client-existant', 'autre-client']);

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ client_name }}', $normalizedName)
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($clientName, $constraint);
    }

    /**
     * Teste la validation avec un nom existant mais différente casse.
     *
     * @return void
     */
    public function testValidateWithExistingClientNameDifferentCase(): void
    {
        $constraint = new UniqueClientName();
        $clientName = 'NOUVEAU CLIENT';
        $normalizedName = 'nouveau-client';

        $this->clientNameNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($clientName)
            ->willReturn($normalizedName);

        $this->environmentServices
            ->expects($this->once())
            ->method('getFolder')
            ->with(FileSystemEnvironmentServices::PROJECT_IN_GENERATOR_ROOT_DIRECTORY)
            ->willReturn(['Nouveau-Client', 'autre-client']); // Différente casse

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ client_name }}', $normalizedName)
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($clientName, $constraint);
    }

    /**
     * Teste la validation avec une erreur du système de fichiers.
     *
     * @return void
     */
    public function testValidateWithFilesystemError(): void
    {
        $constraint = new UniqueClientName();
        $clientName = 'test-client';

        $this->clientNameNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($clientName)
            ->willReturn('test-client');

        $this->environmentServices
            ->expects($this->once())
            ->method('getFolder')
            ->with(FileSystemEnvironmentServices::PROJECT_IN_GENERATOR_ROOT_DIRECTORY)
            ->willThrowException(new \Exception('Filesystem error'));

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->filesystemErrorMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($clientName, $constraint);
    }

    /**
     * Teste la validation avec des valeurs nulles ou vides.
     *
     * @return void
     */
    public function testValidateWithNullAndEmptyValues(): void
    {
        $constraint = new UniqueClientName();

        $this->clientNameNormalizer->expects($this->never())->method('normalize');
        $this->environmentServices->expects($this->never())->method('getFolder');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate(null, $constraint);
        $this->validator->validate('', $constraint);
    }

    /**
     * Teste la validation avec un type de valeur incorrect.
     *
     * @return void
     */
    public function testValidateWithInvalidValueType(): void
    {
        $constraint = new UniqueClientName();

        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedValueException::class);

        $this->validator->validate(123, $constraint);
    }
}
