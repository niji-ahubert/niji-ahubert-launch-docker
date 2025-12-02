<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Tests d'intégration pour EnvironmentServices.
 */
final class EnvironmentServicesIntegrationTest extends KernelTestCase
{
    private FileSystemEnvironmentServices $environmentServices;
    private Filesystem $filesystem;
    private SerializerInterface $serializer;
    private Generator $generator;
    private string $testRootDirectory;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->filesystem = $container->get(Filesystem::class);
        $this->serializer = $container->get(SerializerInterface::class);
        $this->generator = $container->get(Generator::class);

        // Utilisation d'un répertoire temporaire pour les tests
        $this->testRootDirectory = sys_get_temp_dir() . '/socle_test_' . uniqid();
        $this->filesystem->mkdir($this->testRootDirectory);

        // Création d'une instance de service avec un répertoire de test
        $this->environmentServices = new class($this->filesystem, $this->serializer, $this->testRootDirectory) extends FileSystemEnvironmentServices {
            private string $testRootDirectory;

            public function __construct(Filesystem $filesystem, SerializerInterface $serializer, string $testRootDirectory)
            {
                parent::__construct($filesystem, $serializer);
                $this->testRootDirectory = $testRootDirectory;
            }

            public function getPathClient(string $clientName): string
            {
                return sprintf('%s/%s', $this->testRootDirectory, $clientName);
            }
        };
    }

    protected function tearDown(): void
    {
        // Nettoyage du répertoire de test
        if ($this->filesystem->exists($this->testRootDirectory)) {
            $this->filesystem->remove($this->testRootDirectory);
        }
        parent::tearDown();
    }

    /**
     * Teste la mise à jour du nom de client dans les fichiers socle.json.
     */
    public function testUpdateClientNameInSocleFiles(): void
    {
        // Arrange
        $oldClientName = 'ancien-client';
        $newClientName = 'nouveau-client';

        // Création de la structure de test
        $this->createTestProjectStructure($oldClientName);

        // Act
        $modifiedCount = $this->environmentServices->updateClientNameInSocleFiles($oldClientName, $newClientName, $this->generator);

        // Assert
        $this->assertEquals(2, $modifiedCount, 'Deux fichiers auraient dû être modifiés');

        // Vérification du contenu des fichiers modifiés
        $this->assertSocleFileClientName($oldClientName, 'projet1', $newClientName);
        $this->assertSocleFileClientName($oldClientName, 'projet2', $newClientName);

        // Vérification que le fichier avec un autre client n'a pas été modifié
        $this->assertSocleFileClientName('autre-client', 'projet3', 'autre-client');
    }

    /**
     * Teste la mise à jour avec aucun fichier correspondant.
     */
    public function testUpdateClientNameInSocleFilesWithNoMatchingFiles(): void
    {
        // Arrange
        $oldClientName = 'client-inexistant';
        $newClientName = 'nouveau-client';

        // Création de la structure de test avec un autre client
        $this->createTestProjectStructure('autre-client');

        // Act
        $modifiedCount = $this->environmentServices->updateClientNameInSocleFiles($oldClientName, $newClientName, $this->generator);

        // Assert
        $this->assertEquals(0, $modifiedCount, 'Aucun fichier n\'aurait dû être modifié');
    }

    /**
     * Teste la gestion d'erreur lors de la lecture d'un fichier JSON invalide.
     */
    public function testUpdateClientNameInSocleFilesWithInvalidJson(): void
    {
        // Arrange
        $oldClientName = 'ancien-client';
        $newClientName = 'nouveau-client';

        // Création d'un fichier socle.json invalide
        $clientDir = $this->testRootDirectory . '/' . $oldClientName . '/projet1';
        $this->filesystem->mkdir($clientDir . '/config');
        $this->filesystem->dumpFile($clientDir . '/config/socle.json', '{invalid json}');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Erreur lors de la mise à jour des fichiers socle\.json/');

        $this->environmentServices->updateClientNameInSocleFiles($oldClientName, $newClientName, $this->generator);
    }

    /**
     * Crée une structure de test avec plusieurs projets et clients.
     *
     * @param string $mainClientName Nom du client principal pour les tests
     */
    private function createTestProjectStructure(string $mainClientName): void
    {
        // Projet 1 pour le client principal
        $this->createTestProject($mainClientName, 'projet1');

        // Projet 2 pour le client principal
        $this->createTestProject($mainClientName, 'projet2');

        // Projet 3 pour un autre client (ne doit pas être modifié)
        $this->createTestProject('autre-client', 'projet3');
    }

    /**
     * Crée un projet de test avec un fichier socle.json.
     *
     * @param string $clientName Nom du client
     * @param string $projectName Nom du projet
     */
    private function createTestProject(string $clientName, string $projectName): void
    {
        $projectDir = $this->testRootDirectory . '/' . $clientName . '/' . $projectName;
        $configDir = $projectDir . '/config';

        $this->filesystem->mkdir($configDir);

        // Création d'un objet Project
        $project = new Project();
        $project->setClient($clientName);
        $project->setProject($projectName);

        // Sérialisation et sauvegarde
        $jsonContent = $this->serializer->serialize($project, 'json');
        $this->filesystem->dumpFile($configDir . '/socle.json', $jsonContent);
    }

    /**
     * Vérifie que le fichier socle.json contient le bon nom de client.
     *
     * @param string $clientName Nom du client (pour le chemin)
     * @param string $projectName Nom du projet
     * @param string $expectedClientName Nom de client attendu dans le fichier
     */
    private function assertSocleFileClientName(string $clientName, string $projectName, string $expectedClientName): void
    {
        $socleFilePath = $this->testRootDirectory . '/' . $clientName . '/' . $projectName . '/config/socle.json';

        $this->assertFileExists($socleFilePath, "Le fichier socle.json devrait exister pour {$clientName}/{$projectName}");

        $jsonContent = file_get_contents($socleFilePath);
        $this->assertNotFalse($jsonContent, "Le contenu du fichier devrait être lisible");

        /** @var Project $project */
        $project = $this->serializer->deserialize($jsonContent, Project::class, 'json');

        $this->assertEquals(
            $expectedClientName,
            $project->getClient(),
            "Le nom de client dans {$clientName}/{$projectName}/config/socle.json devrait être '{$expectedClientName}'"
        );
    }
}
