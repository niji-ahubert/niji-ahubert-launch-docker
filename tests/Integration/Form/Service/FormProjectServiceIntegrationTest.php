<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Service;

use App\Form\Model\ProjectModel;
use App\Form\Service\FormProjectService;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests d'intégration pour les fonctionnalités de renommage de projet.
 */
final class FormProjectServiceIntegrationTest extends KernelTestCase
{
    private FormProjectService $formProjectService;
    private FileSystemEnvironmentServices $environmentServices;
    private Generator $generator;
    private Filesystem $filesystem;
    private string $testRootDir;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->formProjectService = $container->get(FormProjectService::class);
        $this->environmentServices = $container->get(FileSystemEnvironmentServices::class);
        $this->generator = $container->get(Generator::class);
        $this->filesystem = new Filesystem();

        // Créer un répertoire de test temporaire
        $this->testRootDir = sys_get_temp_dir() . '/socle_test_' . uniqid();
        $this->filesystem->mkdir($this->testRootDir);

        // Utiliser la réflexion pour modifier le ROOT_DIRECTORY pour les tests
        $reflection = new \ReflectionClass($this->environmentServices);
        $property = $reflection->getProperty('ROOT_DIRECTORY');
        $property->setAccessible(true);
        $property->setValue($this->environmentServices, $this->testRootDir);
    }

    protected function tearDown(): void
    {
        // Nettoyer le répertoire de test
        if ($this->filesystem->exists($this->testRootDir)) {
            $this->filesystem->remove($this->testRootDir);
        }
    }

    /**
     * Test d'intégration complet : création, modification et renommage d'un projet.
     */
    public function testCompleteProjectLifecycleWithRename(): void
    {
        $clientName = 'test-client';
        $originalProjectName = 'original-project';
        $newProjectName = 'renamed-project';

        // 1. Créer la structure de répertoires et le projet initial
        $this->createTestProjectStructure($clientName, $originalProjectName);

        // 2. Charger le projet existant
        $originalProject = new Project();
        $originalProject->setClient($clientName);
        $originalProject->setProject($originalProjectName);

        $projectModel = $this->formProjectService->loadedProject($originalProject);
        $this->assertInstanceOf(ProjectModel::class, $projectModel);
        $this->assertEquals($originalProjectName, $projectModel->getProject());
        $this->assertEquals(
            ['client' => $clientName, 'project' => $originalProjectName],
            $projectModel->getOriginalProjectData()
        );

        // 3. Modifier le nom du projet
        $projectModel->setProject($newProjectName);

        // 4. Sauvegarder avec renommage
        $result = $this->formProjectService->saveProject($projectModel, $originalProject);
        $this->assertTrue($result);

        // 5. Vérifier que l'ancien répertoire n'existe plus
        $oldProjectPath = sprintf('%s/%s/%s', $this->testRootDir, $clientName, $originalProjectName);
        $this->assertFalse($this->filesystem->exists($oldProjectPath));

        // 6. Vérifier que le nouveau répertoire existe
        $newProjectPath = sprintf('%s/%s/%s', $this->testRootDir, $clientName, $newProjectName);
        $this->assertTrue($this->filesystem->exists($newProjectPath));

        // 7. Vérifier que le fichier socle.json a été mis à jour
        $socleJsonPath = sprintf('%s/%s/%s/config/socle.json', $this->testRootDir, $clientName, $newProjectName);
        $this->assertTrue($this->filesystem->exists($socleJsonPath));

        $socleContent = json_decode(file_get_contents($socleJsonPath), true);
        $this->assertEquals($clientName, $socleContent['client']);
        $this->assertEquals($newProjectName, $socleContent['project']);
    }

    /**
     * Test d'erreur lors du renommage vers un projet existant.
     */
    public function testRenameToExistingProjectError(): void
    {
        $clientName = 'test-client';
        $originalProjectName = 'original-project';
        $existingProjectName = 'existing-project';

        // 1. Créer deux projets existants
        $this->createTestProjectStructure($clientName, $originalProjectName);
        $this->createTestProjectStructure($clientName, $existingProjectName);

        // 2. Charger le projet original
        $originalProject = new Project();
        $originalProject->setClient($clientName);
        $originalProject->setProject($originalProjectName);

        $projectModel = $this->formProjectService->loadedProject($originalProject);
        $this->assertInstanceOf(ProjectModel::class, $projectModel);

        // 3. Tenter de renommer vers un projet existant
        $projectModel->setProject($existingProjectName);

        // 4. Vérifier que l'erreur est levée
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Impossible de renommer le répertoire du projet/');

        $this->formProjectService->saveProject($projectModel, $originalProject);
    }

    /**
     * Test de sauvegarde sans renommage (nom inchangé).
     */
    public function testSaveWithoutRename(): void
    {
        $clientName = 'test-client';
        $projectName = 'test-project';

        // 1. Créer la structure de test
        $this->createTestProjectStructure($clientName, $projectName);

        // 2. Charger le projet
        $project = new Project();
        $project->setClient($clientName);
        $project->setProject($projectName);

        $projectModel = $this->formProjectService->loadedProject($project);
        $this->assertInstanceOf(ProjectModel::class, $projectModel);

        // 3. Modifier une autre propriété (pas le nom)
        $projectModel->setTraefikNetwork('custom-network');

        // 4. Sauvegarder
        $result = $this->formProjectService->saveProject($projectModel, $project);
        $this->assertTrue($result);

        // 5. Vérifier que le répertoire existe toujours
        $projectPath = sprintf('%s/%s/%s', $this->testRootDir, $clientName, $projectName);
        $this->assertTrue($this->filesystem->exists($projectPath));

        // 6. Vérifier que le fichier a été mis à jour
        $socleJsonPath = sprintf('%s/%s/%s/config/socle.json', $this->testRootDir, $clientName, $projectName);
        $socleContent = json_decode(file_get_contents($socleJsonPath), true);
        $this->assertEquals('custom-network', $socleContent['traefikNetwork']);
    }

    /**
     * Crée une structure de projet de test avec fichier socle.json.
     *
     * @param string $clientName Nom du client
     * @param string $projectName Nom du projet
     */
    private function createTestProjectStructure(string $clientName, string $projectName): void
    {
        $projectPath = sprintf('%s/%s/%s', $this->testRootDir, $clientName, $projectName);
        $configPath = sprintf('%s/config', $projectPath);
        $socleJsonPath = sprintf('%s/socle.json', $configPath);

        // Créer les répertoires
        $this->filesystem->mkdir($configPath);

        // Créer le fichier socle.json
        $socleData = [
            'client' => $clientName,
            'project' => $projectName,
            'traefikNetwork' => 'public-dev',
            'environmentContainer' => 'DEV',
            'serviceContainer' => []
        ];

        $this->filesystem->dumpFile($socleJsonPath, json_encode($socleData, JSON_PRETTY_PRINT));
    }
}
