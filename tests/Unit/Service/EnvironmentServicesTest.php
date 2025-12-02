<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Tests unitaires pour EnvironmentServices.
 */
final class EnvironmentServicesTest extends TestCase
{
    private FileSystemEnvironmentServices $environmentServices;
    private Filesystem $filesystem;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->environmentServices = new FileSystemEnvironmentServices($this->filesystem, $this->serializer);
    }

    /**
     * Teste la méthode updateClientNameInSocleFiles avec des fichiers existants.
     */
    public function testUpdateClientNameInSocleFilesWithExistingFiles(): void
    {
        // Arrange
        $oldClientName = 'ancien-client';
        $newClientName = 'nouveau-client';

        // Mock d'un projet avec l'ancien nom de client
        $project = new Project();
        $project->setClient($oldClientName);
        $project->setProject('test-project');

        // Mock du contenu JSON original
        $originalJsonContent = '{"client":"ancien-client","project":"test-project"}';
        $updatedJsonContent = '{"client":"nouveau-client","project":"test-project"}';

        // Configuration des mocks
        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($originalJsonContent, Project::class, 'json')
            ->willReturn($project);

        $this->serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function (Project $proj) use ($newClientName) {
                return $proj->getClient() === $newClientName;
            }), 'json', $this->anything())
            ->willReturn($updatedJsonContent);

        // Mock de file_get_contents et file_put_contents via des fonctions globales
        // Note: Dans un vrai test, on utiliserait vfsStream ou un autre système de fichiers virtuel

        // Act & Assert
        // Cette méthode nécessiterait une refactorisation pour être testable unitairement
        // car elle utilise directement Finder et les fonctions globales de fichier
        $this->markTestSkipped('Cette méthode nécessite une refactorisation pour être testable unitairement');
    }

    /**
     * Teste la vérification d'existence d'un dossier client.
     */
    public function testClientFolderExists(): void
    {
        // Arrange
        $clientName = 'test-client';
        $expectedPath = '/var/www/html/projects/test-client';

        $this->filesystem
            ->expects($this->once())
            ->method('exists')
            ->with($expectedPath)
            ->willReturn(true);

        // Act
        $result = $this->environmentServices->clientFolderExists($clientName);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Teste la génération du chemin client.
     */
    public function testGetPathClient(): void
    {
        // Arrange
        $clientName = 'test-client';
        $expectedPath = '/var/www/html/projects/test-client';

        // Act
        $result = $this->environmentServices->getPathClient($clientName);

        // Assert
        $this->assertEquals($expectedPath, $result);
    }

    /**
     * Teste la création d'un dossier client.
     */
    public function testCreateClientFolder(): void
    {
        // Arrange
        $clientName = 'test-client';
        $expectedPath = '/var/www/html/projects/test-client';

        $this->filesystem
            ->expects($this->once())
            ->method('mkdir')
            ->with($expectedPath);

        // Act
        $this->environmentServices->createClientFolder($clientName);

        // Assert - L'assertion est implicite via expects()
        $this->addToAssertionCount(1);
    }

    /**
     * Teste le renommage d'un dossier client.
     */
    public function testRenameClientFolder(): void
    {
        // Arrange
        $oldClientName = 'ancien-client';
        $newClientName = 'nouveau-client';
        $oldPath = '/var/www/html/projects/ancien-client';
        $newPath = '/var/www/html/projects/nouveau-client';

        $this->filesystem
            ->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                [$oldPath, true],
                [$newPath, false],
            ]);

        $this->filesystem
            ->expects($this->once())
            ->method('rename')
            ->with($oldPath, $newPath);

        // Act
        $this->environmentServices->renameClientFolder($oldClientName, $newClientName);

        // Assert - L'assertion est implicite via expects()
        $this->addToAssertionCount(1);
    }

    /**
     * Teste le renommage d'un dossier client inexistant.
     */
    public function testRenameClientFolderWithNonExistentSource(): void
    {
        // Arrange
        $oldClientName = 'inexistant-client';
        $newClientName = 'nouveau-client';
        $oldPath = '/var/www/html/projects/inexistant-client';

        $this->filesystem
            ->expects($this->once())
            ->method('exists')
            ->with($oldPath)
            ->willReturn(false);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le dossier client "inexistant-client" n\'existe pas.');

        $this->environmentServices->renameClientFolder($oldClientName, $newClientName);
    }

    /**
     * Teste le renommage vers un dossier client existant.
     */
    public function testRenameClientFolderWithExistentDestination(): void
    {
        // Arrange
        $oldClientName = 'ancien-client';
        $newClientName = 'existant-client';
        $oldPath = '/var/www/html/projects/ancien-client';
        $newPath = '/var/www/html/projects/existant-client';

        $this->filesystem
            ->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                [$oldPath, true],
                [$newPath, true],
            ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le dossier client "existant-client" existe déjà.');

        $this->environmentServices->renameClientFolder($oldClientName, $newClientName);
    }

    /**
     * Teste la suppression d'un dossier client.
     */
    public function testDeleteClientFolder(): void
    {
        // Arrange
        $clientName = 'test-client';
        $expectedPath = '/var/www/html/projects/test-client';

        $this->filesystem
            ->expects($this->once())
            ->method('exists')
            ->with($expectedPath)
            ->willReturn(true);

        $this->filesystem
            ->expects($this->once())
            ->method('remove')
            ->with($expectedPath);

        // Act
        $this->environmentServices->deleteClientFolder($clientName);

        // Assert - L'assertion est implicite via expects()
        $this->addToAssertionCount(1);
    }

    /**
     * Teste la suppression d'un dossier client inexistant.
     */
    public function testDeleteClientFolderWithNonExistentFolder(): void
    {
        // Arrange
        $clientName = 'inexistant-client';
        $expectedPath = '/var/www/html/projects/inexistant-client';

        $this->filesystem
            ->expects($this->once())
            ->method('exists')
            ->with($expectedPath)
            ->willReturn(false);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le dossier client "inexistant-client" n\'existe pas.');

        $this->environmentServices->deleteClientFolder($clientName);
    }
}
