<?php

declare(strict_types=1);

namespace App\Services\Generation;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\DockerAction;
use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\DockerCompose\CreateDockerService;
use App\Services\DockerCompose\DockerComposeFile;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\StrategyManager\CreateApplicationService;
use App\Util\DockerUtility;
use Monolog\Level;

final readonly class ProjectGenerationService
{
    public function __construct(
        private DockerComposeFile $dockerComposeFileService,
        private CreateDockerService $createDockerService,
        private CreateApplicationService $createApplicationService,
        private FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private MercureService $mercureService,
        private StartProjectService $startProjectService,
        private TaskfileGenerationService $taskfileGenerationService,
    ) {
    }

    /**
     * Génère le projet complet (docker-compose + services + applications)
     * avec publication en temps réel via Mercure.
     */
    public function generateCompleteProject(Project $project): void
    {
        try {
            $this->mercureService->initialize($project, LoggerChannel::BUILD);

            $this->mercureService->dispatch(
                message: '📦 Début de la génération du projet',
                type: TypeLog::START,
            );

            $this->mercureService->dispatch(message: '📦 Génération du fichier docker-compose.yml');
            $this->dockerComposeFileService->getDockerComposeFile($project);
            $this->mercureService->dispatch(message: '✅ Fichier docker-compose.yml généré avec succès');
        } catch (\Exception $exception) {
            $this->mercureService->dispatch(
                message: '❌ Erreur lors de la génération du docker-compose.yml',
                type: TypeLog::ERROR,
                level: Level::Error,
                error: $exception->getMessage(),
            );

            return;
        }

        $this->mercureService->dispatch(
            message: '📦 Création dossier logs',
        );

        try {
            $this->fileSystemEnvironmentServices->createProjectLogsFolder($project);

            $this->mercureService->dispatch(
                message: '✅ Création dossier logs généré avec succès',
            );
        } catch (\Exception $exception) {
            $this->mercureService->dispatch(
                message: '❌ Erreur lors de la création du dossier logs',
                type: TypeLog::ERROR,
                level: Level::Error,
                error: $exception->getMessage(),
            );
        }

        $this->mercureService->dispatch(
            message: '📦 Création dossier bin',
        );

        try {
            $this->fileSystemEnvironmentServices->createProjectBinFolder($project);

            $this->mercureService->dispatch(
                message: '✅ Création dossier bin généré avec succès',
            );
        } catch (\Exception $exception) {
            $this->mercureService->dispatch(
                message: '❌ Erreur lors de la création du dossier bin',
                type: TypeLog::ERROR,
                level: Level::Error,
                error: $exception->getMessage(),
            );
        }

        $this->mercureService->dispatch(
            message: '📦 Création dossier docker',
        );

        try {
            $this->fileSystemEnvironmentServices->createProjectDockerFolder($project);

            $this->mercureService->dispatch(
                message: '✅ Création du dossier docker généré avec succès',
            );
        } catch (\Exception $exception) {
            $this->mercureService->dispatch(
                message: '❌ Erreur lors de la création du dossier docker',
                type: TypeLog::ERROR,
                level: Level::Error,
                error: $exception->getMessage(),
            );
        }

        $this->executeCreateDockerService($project);

        $this->executeCreateApplicationService($project, DockerAction::BUILD);

        $this->taskfileGenerationService->generate($project);

        $this->mercureService->dispatch(
            message: '🎉 Génération du projet terminée avec succès',
            type: TypeLog::COMPLETE,
        );
    }

    public function executeCreateApplicationService(Project $project, DockerAction $dockerAction, bool $onlyProjectService = false): void
    {
        $this->mercureService->initialize($project, LoggerChannel::BUILD);

        foreach ($project->getServiceContainer() as $service) {
            if ($onlyProjectService && $service->getServiceContainer() instanceof ServiceContainer) {
                continue;
            }

            if (DockerAction::START === $dockerAction) {
                $this->startProjectService->waitForServiceToBeReady($project, $service);
            }

            if (!$service->getServiceContainer() instanceof ProjectContainer) {
                continue;
            }

            $serviceName = $service->getDockerServiceName();

            $this->executeWithMercureNotification(
                project: $project,
                serviceName: $serviceName,
                startMessage: \sprintf('🚀 Génération du dossier application %s', $serviceName),
                successMessage: \sprintf('✅ Application %s générée avec succès', $serviceName),
                errorMessage: \sprintf('❌ Erreur lors de la génération de l\'application %s', $serviceName),
                callback: fn () => ($this->createApplicationService)($service, $project, $dockerAction),
            );
        }
    }

    private function executeCreateDockerService(Project $project): void
    {
        foreach ($project->getServiceContainer() as $service) {
            $serviceName = $this->getServiceName($service);

            $this->executeWithMercureNotification(
                project: $project,
                serviceName: null,
                startMessage: \sprintf('🔧 Ajout du service %s dans docker-compose', $serviceName),
                successMessage: \sprintf('✅ Service %s ajouté avec succès', $serviceName),
                errorMessage: \sprintf('❌ Erreur lors de l\'ajout du service %s', $serviceName),
                callback: fn () => ($this->createDockerService)($service, $project),
            );
        }
    }

    private function executeWithMercureNotification(
        Project $project,
        ?string $serviceName,
        string $startMessage,
        string $successMessage,
        string $errorMessage,
        callable $callback,
    ): void {
        $this->mercureService->dispatch(message: $startMessage);

        try {
            $callback();
            $this->mercureService->dispatch(message: $successMessage);

            // Récupérer les logs du container si un service est spécifié
            if (null !== $serviceName) {
                $this->publishContainerLogs($project, $serviceName);
            }
        } catch (\Exception $exception) {
            $this->mercureService->dispatch(
                message: $errorMessage,
                type: TypeLog::ERROR,
                level: Level::Error,
                error: $exception->getMessage(),
            );
        }
    }

    /**
     * Récupère et publie les logs du container via Mercure.
     */
    private function publishContainerLogs(Project $project, string $serviceName): void
    {
        try {
            $dockerComposeFilePath = $this->fileSystemEnvironmentServices->getDockerComposeFilePath($project);

            // Récupérer les dernières 50 lignes de logs
            $logs = DockerUtility::getLogContainer(
                project: $project,
                serviceName: $serviceName,
                dockerComposeFilePath: $dockerComposeFilePath,
                follow: false,
                tail: 50,
            );

            if ('' !== $logs && '0' !== $logs) {
                $this->mercureService->dispatch(
                    message: \sprintf('📋 Logs du container %s:', $serviceName),
                    level: Level::Debug,
                );

                // Publier les logs ligne par ligne
                $logLines = explode("\n", trim($logs));
                foreach ($logLines as $logLine) {
                    if (!\in_array(trim($logLine), ['', '0'], true)) {
                        $this->mercureService->dispatch(
                            message: $logLine,
                            level: Level::Debug,
                        );
                    }
                }
            }
        } catch (\Exception $exception) {
            // Ne pas bloquer le processus si la récupération des logs échoue
            $this->mercureService->dispatch(
                message: \sprintf('⚠️ Impossible de récupérer les logs du container %s: %s', $serviceName, $exception->getMessage()),
                level: Level::Warning,
            );
        }
    }

    private function getServiceName(AbstractContainer $service): string
    {
        return $service->getServiceContainer() instanceof ServiceContainer
            ? $service->getServiceContainer()->value
            : (string) $service->getFolderName();
    }
}
