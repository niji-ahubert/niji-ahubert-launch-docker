<?php

declare(strict_types=1);

namespace App\Form\Service;

use App\Form\DataTransformer\ProjectModelToProjectTransformer;
use App\Form\Model\ProjectModel;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Component\Finder\Finder;
use Webmozart\Assert\Assert;

final readonly class FormProjectService
{
    private Finder $finder;

    public function __construct(
        private ProjectModelToProjectTransformer $projectModelToProjectTransformer,
        private FileSystemEnvironmentServices $environmentServices,
    ) {
        $this->finder = new Finder();
    }

    /**
     * Charge un projet existant et initialise les données originales.
     *
     * @param Project $project Projet à charger
     */
    public function loadedProject(Project $project): ProjectModel|false
    {
        if (!($loadedProject = $this->environmentServices->loadEnvironments($project)) instanceof Project) {
            return false;
        }

        $projectModel = $this->projectModelToProjectTransformer->transform($loadedProject);

        // Définir les données originales pour détecter les changements
        $projectModel->setOriginalProjectData($loadedProject->getClient(), $loadedProject->getProject());

        return $projectModel;
    }

    /**
     * Sauvegarde un projet et gère le renommage du répertoire si nécessaire.
     *
     * @param ProjectModel $projectModel Modèle du projet à sauvegarder
     * @param Project|null $project      Projet original (pour la modification)
     *
     * @throws \RuntimeException En cas d'erreur lors du renommage du répertoire
     */
    public function saveProject(ProjectModel $projectModel, ?Project $project = null): bool
    {
        if (!$project instanceof Project || !($currentProject = $this->environmentServices->loadEnvironments($project)) instanceof Project) {
            $currentProject = new Project();
        }

        // Détecter si le nom du projet a changé
        $originalData = $projectModel->getOriginalProjectData();
        $hasProjectNameChanged = null !== $originalData
            && null !== $originalData['project']
            && $originalData['project'] !== $projectModel->getProject();

        // Si le nom du projet a changé, renommer le répertoire avant de sauvegarder
        if ($hasProjectNameChanged && null !== $projectModel->getClient()) {
            try {
                Assert::String($projectModel->getClient());
                Assert::String($originalData['project']);
                Assert::String($projectModel->getProject());

                $this->environmentServices->renameProjectFolder(
                    $projectModel->getClient(),
                    $originalData['project'],
                    $projectModel->getProject(),
                );

                // Créer un projet temporaire avec le nouveau nom pour mettre à jour le pathProject
                $updatedProject = $this->projectModelToProjectTransformer->reverseTransform($projectModel, $currentProject);
                $this->environmentServices->updatePathProject($updatedProject);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException(\sprintf('Impossible de renommer le répertoire du projet : %s', $e->getMessage()), 0, $e);
            }
        } else {
            // Pour un nouveau projet ou sans changement de nom, transformer normalement
            $updatedProject = $this->projectModelToProjectTransformer->reverseTransform($projectModel, $currentProject);
        }

        // Sauvegarder le projet (le pathProject est maintenant correct)
        $this->environmentServices->saveEnvironments($updatedProject);

        return true;
    }

    /**
     * @return array<Project|null>
     */
    public function getProjects(Project $project): array
    {
        $clientPath = $this->environmentServices->getPathClient($project->getClient());
        $projects = [];
        if (file_exists($clientPath)) {
            $projectFinder = $this->finder->directories()->in($clientPath)->depth(0);
            foreach ($projectFinder as $projectDir) {
                $projectName = $projectDir->getRelativePathname();
                $project = new Project()
                    ->setClient($project->getClient())
                    ->setProject($projectName);

                if (($loadedProject = $this->environmentServices->loadEnvironments($project)) instanceof Project) {
                    $projects[] = $loadedProject;
                } else {
                    $projects[] = $project;
                }
            }
        }

        return $projects;
    }
}
