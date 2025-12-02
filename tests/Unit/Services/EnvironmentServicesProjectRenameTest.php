<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Services\FileSystemEnvironmentServices;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Tests unitaires pour les fonctionnalités de renommage de projet dans EnvironmentServices.
 */
final class EnvironmentServicesProjectRenameTest extends TestCase
{
    private FileSystemEnvironmentServices $environmentServices;
    private Filesystem $filesystem;
    private SerializerInterface $serializer;
    private Generator $generator;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->generator = $this->createMock(Generator::class);

        $this->environmentServices = new FileSystemEnvironmentServices(
            $this->filesystem,
            $this->serializer,
            $this->generator
        );
    }

    /**
     * Test du renommage réussi d'un répertoire de projet.
     */
    public function testRenameProjectFolderSuccess(): void
    {
        $clientName = 'test-client';
        $oldProjectName = 'old-project';
        $newProjectName = 'new-project';

        $oldProjectPath = '/var/www/html/projects/test-client/old-project';
        $newProjectPath = '/var/www/html/projects/test-client/new-project';

        // Mock filesystem : le répertoire source existe, le répertoire destination n'existe pas
        $this->filesystem
            ->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                [$oldProjectPath, true],
                [$newProjectPath, false],
            ]);

        // Mock filesystem : le renommage est effectué
        $this->filesystem
            ->expects($this->once())
            ->method('rename')
            ->with($oldProjectPath, $newProjectPath);

        $this->environmentServices->renameProjectFolder($clientName, $oldProjectName, $newProjectName);
    }

    /**
     * Test d'erreur quand le répertoire source n'existe pas.
     */
    public function testRenameProjectFolderSourceNotExists(): void
    {
        $clientName = 'test-client';
        $oldProjectName = 'non-existent-project';
        $newProjectName = 'new-project';

        $oldProjectPath = '/var/www/html/projects/test-client/non-existent-project';

        // Mock filesystem : le répertoire source n'existe pas
        $this->filesystem
            ->expects($this->once())
            ->method('exists')
            ->with($oldProjectPath)
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le répertoire du projet "test-client/non-existent-project" n\'existe pas.');

        $this->environmentServices->renameProjectFolder($clientName, $oldProjectName, $newProjectName);
    }

    /**
     * Test d'erreur quand le répertoire destination existe déjà.
     */
    public function testRenameProjectFolderDestinationExists(): void
    {
        $clientName = 'test-client';
        $oldProjectName = 'old-project';
        $newProjectName = 'existing-project';

        $oldProjectPath = '/var/www/html/projects/test-client/old-project';
        $newProjectPath = '/var/www/html/projects/test-client/existing-project';

        // Mock filesystem : les deux répertoires existent
        $this->filesystem
            ->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                [$oldProjectPath, true],
                [$newProjectPath, true],
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le répertoire du projet "test-client/existing-project" existe déjà.');

        $this->environmentServices->renameProjectFolder($clientName, $oldProjectName, $newProjectName);
    }

    /**
     * Test de vérification d'existence d'un répertoire de projet.
     */
    public function testProjectFolderExists(): void
    {
        $clientName = 'test-client';
        $projectName = 'test-project';
        $projectPath = '/var/www/html/projects/test-client/test-project';

        // Test : le répertoire existe
        $this->filesystem
            ->expects($this->once())
            ->method('exists')
            ->with($projectPath)
            ->willReturn(true);

        $result = $this->environmentServices->projectFolderExists($clientName, $projectName);
        $this->assertTrue($result);
    }

    /**
     * Test de vérification d'existence d'un répertoire de projet inexistant.
     */
    public function testProjectFolderNotExists(): void
    {
        $clientName = 'test-client';
        $projectName = 'non-existent-project';
        $projectPath = '/var/www/html/projects/test-client/non-existent-project';

        // Test : le répertoire n'existe pas
        $this->filesystem
            ->expects($this->once())
            ->method('exists')
            ->with($projectPath)
            ->willReturn(false);

        $result = $this->environmentServices->projectFolderExists($clientName, $projectName);
        $this->assertFalse($result);
    }
}
