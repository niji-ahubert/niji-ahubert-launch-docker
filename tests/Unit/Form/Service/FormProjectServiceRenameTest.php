<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Service;

use App\Form\DataTransformer\ProjectModelToProjectTransformer;
use App\Form\Model\ProjectModel;
use App\Form\Service\FormProjectService;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Tests unitaires pour les fonctionnalités de renommage de projet dans FormProjectService.
 */
final class FormProjectServiceRenameTest extends TestCase
{
    private FormProjectService $formProjectService;
    private ProjectModelToProjectTransformer $transformer;
    private FileSystemEnvironmentServices $environmentServices;
    private Generator $generator;

    protected function setUp(): void
    {
        $this->transformer = $this->createMock(ProjectModelToProjectTransformer::class);
        $this->environmentServices = $this->createMock(FileSystemEnvironmentServices::class);
        $this->generator = $this->createMock(Generator::class);

        $this->formProjectService = new FormProjectService(
            $this->transformer,
            $this->environmentServices,
            $this->generator
        );
    }

    /**
     * Test de sauvegarde d'un nouveau projet (sans renommage).
     */
    public function testSaveNewProject(): void
    {
        $projectModel = new ProjectModel();
        $projectModel->setClient('test-client');
        $projectModel->setProject('new-project');

        $newProject = new Project();
        $updatedProject = new Project();

        // Mock : pas de projet existant
        $this->environmentServices
            ->expects($this->never())
            ->method('loadEnvironments');

        // Mock : transformation du modèle
        $this->transformer
            ->expects($this->once())
            ->method('reverseTransform')
            ->with($projectModel, $this->isInstanceOf(Project::class))
            ->willReturn($updatedProject);

        // Mock : sauvegarde
        $this->environmentServices
            ->expects($this->once())
            ->method('saveEnvironments')
            ->with($updatedProject, $this->generator);

        $result = $this->formProjectService->saveProject($projectModel);
        $this->assertTrue($result);
    }

    /**
     * Test de sauvegarde d'un projet existant sans changement de nom.
     */
    public function testSaveExistingProjectWithoutRename(): void
    {
        $projectModel = new ProjectModel();
        $projectModel->setClient('test-client');
        $projectModel->setProject('existing-project');
        $projectModel->setOriginalProjectData('test-client', 'existing-project');

        $existingProject = new Project();
        $existingProject->setClient('test-client');
        $existingProject->setProject('existing-project');

        $originalProject = new Project();
        $originalProject->setClient('test-client');
        $originalProject->setProject('existing-project');

        $updatedProject = new Project();

        // Mock : chargement du projet existant
        $this->environmentServices
            ->expects($this->once())
            ->method('loadEnvironments')
            ->with($originalProject)
            ->willReturn($existingProject);

        // Mock : pas de renommage car le nom n'a pas changé
        $this->environmentServices
            ->expects($this->never())
            ->method('renameProjectFolder');

        // Mock : transformation du modèle
        $this->transformer
            ->expects($this->once())
            ->method('reverseTransform')
            ->with($projectModel, $existingProject)
            ->willReturn($updatedProject);

        // Mock : sauvegarde
        $this->environmentServices
            ->expects($this->once())
            ->method('saveEnvironments')
            ->with($updatedProject, $this->generator);

        $result = $this->formProjectService->saveProject($projectModel, $originalProject);
        $this->assertTrue($result);
    }

    /**
     * Test de sauvegarde d'un projet existant avec changement de nom (renommage réussi).
     */
    public function testSaveExistingProjectWithSuccessfulRename(): void
    {
        $projectModel = new ProjectModel();
        $projectModel->setClient('test-client');
        $projectModel->setProject('renamed-project');
        $projectModel->setOriginalProjectData('test-client', 'original-project');

        $existingProject = new Project();
        $existingProject->setClient('test-client');
        $existingProject->setProject('original-project');

        $originalProject = new Project();
        $originalProject->setClient('test-client');
        $originalProject->setProject('original-project');

        $updatedProject = new Project();

        // Mock : chargement du projet existant
        $this->environmentServices
            ->expects($this->once())
            ->method('loadEnvironments')
            ->with($originalProject)
            ->willReturn($existingProject);

        // Mock : renommage du répertoire
        $this->environmentServices
            ->expects($this->once())
            ->method('renameProjectFolder')
            ->with('test-client', 'original-project', 'renamed-project');

        // Mock : transformation du modèle
        $this->transformer
            ->expects($this->once())
            ->method('reverseTransform')
            ->with($projectModel, $existingProject)
            ->willReturn($updatedProject);

        // Mock : sauvegarde
        $this->environmentServices
            ->expects($this->once())
            ->method('saveEnvironments')
            ->with($updatedProject, $this->generator);

        $result = $this->formProjectService->saveProject($projectModel, $originalProject);
        $this->assertTrue($result);
    }

    /**
     * Test d'erreur lors du renommage du répertoire.
     */
    public function testSaveExistingProjectWithRenameError(): void
    {
        $projectModel = new ProjectModel();
        $projectModel->setClient('test-client');
        $projectModel->setProject('renamed-project');
        $projectModel->setOriginalProjectData('test-client', 'original-project');

        $existingProject = new Project();
        $existingProject->setClient('test-client');
        $existingProject->setProject('original-project');

        $originalProject = new Project();
        $originalProject->setClient('test-client');
        $originalProject->setProject('original-project');

        // Mock : chargement du projet existant
        $this->environmentServices
            ->expects($this->once())
            ->method('loadEnvironments')
            ->with($originalProject)
            ->willReturn($existingProject);

        // Mock : erreur lors du renommage
        $this->environmentServices
            ->expects($this->once())
            ->method('renameProjectFolder')
            ->with('test-client', 'original-project', 'renamed-project')
            ->willThrowException(new \RuntimeException('Le répertoire destination existe déjà.'));

        // Mock : pas de transformation ni de sauvegarde en cas d'erreur
        $this->transformer
            ->expects($this->never())
            ->method('reverseTransform');

        $this->environmentServices
            ->expects($this->never())
            ->method('saveEnvironments');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible de renommer le répertoire du projet : Le répertoire destination existe déjà.');

        $this->formProjectService->saveProject($projectModel, $originalProject);
    }

    /**
     * Test de chargement d'un projet avec initialisation des données originales.
     */
    public function testLoadedProjectWithOriginalData(): void
    {
        $project = new Project();
        $project->setClient('test-client');
        $project->setProject('test-project');

        $loadedProject = new Project();
        $loadedProject->setClient('test-client');
        $loadedProject->setProject('test-project');

        $projectModel = new ProjectModel();
        $projectModel->setClient('test-client');
        $projectModel->setProject('test-project');

        // Mock : chargement du projet
        $this->environmentServices
            ->expects($this->once())
            ->method('loadEnvironments')
            ->with($project)
            ->willReturn($loadedProject);

        // Mock : transformation du projet
        $this->transformer
            ->expects($this->once())
            ->method('transform')
            ->with($loadedProject)
            ->willReturn($projectModel);

        $result = $this->formProjectService->loadedProject($project);

        $this->assertInstanceOf(ProjectModel::class, $result);
        $this->assertEquals(['client' => 'test-client', 'project' => 'test-project'], $result->getOriginalProjectData());
    }

    /**
     * Test de chargement d'un projet inexistant.
     */
    public function testLoadedProjectNotFound(): void
    {
        $project = new Project();
        $project->setClient('test-client');
        $project->setProject('non-existent-project');

        // Mock : projet non trouvé
        $this->environmentServices
            ->expects($this->once())
            ->method('loadEnvironments')
            ->with($project)
            ->willReturn(null);

        // Mock : pas de transformation
        $this->transformer
            ->expects($this->never())
            ->method('transform');

        $result = $this->formProjectService->loadedProject($project);
        $this->assertFalse($result);
    }
}
