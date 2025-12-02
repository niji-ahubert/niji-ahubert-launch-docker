<?php

declare(strict_types=1);

namespace App\Services\Generation;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Util\DockerComposeUtility;
use App\Util\DockerUtility;
use App\Util\EnvVarUtility;
use App\Util\ServiceContainerUtility;
use Monolog\Level;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Service pour démarrer les projets Docker.
 *
 * Traduit la logique du script bash startProject.sh en utilisant
 * un objet Project comme paramètre d'entrée. Utilise un système de Generator
 * pour le streaming des événements en temps réel.
 *
 * Les logs sont sauvegardés dans projects/{client}/{project}/logs/start.log
 */
final readonly class StartProjectService
{
    public function __construct(
        private ProcessRunnerService          $processRunnerService,
        private FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private MercureService                $mercureService,

        private string                        $projectDir
    )
    {
    }

    public function startProject(Project $project, bool $onlyProjectService = false): void
    {
        $this->mercureService->initialize($project, LoggerChannel::BUILD);

        $totalServices = \count($project->getServiceContainer());
        $startedServices = 0;

        foreach ($project->getServiceContainer() as $service) {
            try {

                if ($onlyProjectService === true && $service->getServiceContainer() instanceof ServiceContainer) {
                    continue;
                }

                match (true) {
                    $service->getServiceContainer() instanceof ProjectContainer => $this->startService($project, $service),
                    $service->getServiceContainer() instanceof ServiceContainer => $this->startExternalServices($project, $service),
                    default => null,
                };
                $startedServices++;
            } catch (ProcessFailedException $e) {

                $this->mercureService->dispatch(
                    message: sprintf('❌ Échec du démarrage pour le service: %s', $service->getFolderName()),
                    type: TypeLog::ERROR,
                    level: Level::Error,
                    error: $e->getMessage()
                );
            }
        }

        $completeMessage = \sprintf('Démarrage terminé: %d/%d services démarrés avec succès', $startedServices, $totalServices);
        $this->mercureService->dispatch(
            message: $completeMessage,
            type: TypeLog::COMPLETE,
        );
    }

    private function startExternalServices(Project $project, AbstractContainer $service): void
    {

        $command = [
            'docker',
            '--log-level=ERROR',
            'compose',
            '--profile',
            'runner-dev',
            '--project-name',
            DockerUtility::getProjectName($project),
            '-f',
            $this->fileSystemEnvironmentServices->getDockerComposeFilePath($project),
            'up',
            '--detach',
            $service->getServiceContainer()->value
        ];
        $this->processRunnerService->run($command, '📦 Démarrage des services externes', $this->projectDir);

    }

    private function startService(Project $project, AbstractContainer $service): void
    {

        if (!$this->fileSystemEnvironmentServices->componentEnvFileExist($project, $service)) {
            $warningMessage = \sprintf('❌ Fichier d\'environnement non trouvé: %s', $this->fileSystemEnvironmentServices->getComponentEnvFile($project, $service));
            $this->mercureService->dispatch(
                message: $warningMessage,
                type: TypeLog::ERROR,
                level: Level::Error
            );
            return;
        }

        $imageName = DockerUtility::getFinalTagName($project, $service);
        if (!DockerUtility::dockerImageExists($imageName)) {
            $errorMessage = \sprintf('❌ Image Docker non trouvée: %s', $imageName);
            $this->mercureService->dispatch(
                message: $errorMessage,
                type: TypeLog::ERROR,
                level: Level::Error
            );

            return;
        }


        $command = [
            'docker',
            '--log-level=ERROR',
            'compose',
            '--profile',
            'runner-dev',
            '--project-name',
            DockerUtility::getProjectName($project),
            '-f',
            $this->fileSystemEnvironmentServices->getDockerComposeFilePath($project),
            'up',
            '--detach',
            DockerComposeUtility::getProjectServiceName($project, $service)
        ];


        $this->processRunnerService->run(
            $command,
            sprintf('Start Service %s', $service->getFolderName()),
            $this->projectDir,
            env: EnvVarUtility::loadEnvironmentVariables($this->fileSystemEnvironmentServices->getComponentEnvFile($project, $service))
        );


        if (ServiceContainerUtility::isSymfonyDevService($service, $project->getEnvironmentContainer())) {

            $this->mercureService->dispatch(
                message: ServiceContainerUtility::getSymfonyDebugMessage(),
            );
        }
    }

    public function waitForServiceToBeReady(Project $project, AbstractContainer $service, int $timeoutSeconds = 60): void
    {
        $serviceName = match (true) {
            $service->getServiceContainer() instanceof ProjectContainer => DockerComposeUtility::getProjectServiceName($project, $service),
            default => $service->getServiceContainer()->value
        };

        $this->mercureService->dispatch(
            message: sprintf('⏳ Vérification du statut du service: %s', $serviceName),
            type: TypeLog::START
        );

        $startTime = time();
        $checkInterval = 5; // Vérification toutes les 5 secondes
        $command = [];
        while ((time() - $startTime) < $timeoutSeconds) {
            $command = [
                'docker',
                '--log-level=ERROR',
                'compose',
                '--profile',
                'runner-dev',
                '--project-name',
                DockerUtility::getProjectName($project),
                '-f',
                $this->fileSystemEnvironmentServices->getDockerComposeFilePath($project),
                'ps',
                '--format',
                'json',
                $serviceName
            ];

            try {
                $exitCode = $this->processRunnerService->run(
                    $command,
                    sprintf('🔍 Vérification du service %s', $serviceName),
                    $this->projectDir
                );

                if ($exitCode === 0) {
                    // Le service existe et répond, on considère qu'il est prêt
                    $this->mercureService->dispatch(
                        message: sprintf('✅ Service %s est prêt et opérationnel', $serviceName),
                        type: TypeLog::COMPLETE
                    );
                    return;
                }
            } catch (ProcessFailedException $e) {
                // Si la commande échoue, le service pourrait être en erreur
                $this->mercureService->dispatch(
                    message: sprintf('❌ Erreur lors de la vérification du service %s: %s', $serviceName, $e->getMessage()),
                    type: TypeLog::ERROR,
                    level: Level::Error
                );
                throw $e;
            }

            // Attendre avant la prochaine vérification
            $remainingTime = $timeoutSeconds - (time() - $startTime);
            if ($remainingTime > 0) {
                $this->mercureService->dispatch(
                    message: sprintf('⏳ Service %s pas encore prêt, nouvelle vérification dans %d secondes (timeout dans %d secondes)',
                        $serviceName,
                        min($checkInterval, $remainingTime),
                        $remainingTime
                    )
                );
                sleep(min($checkInterval, $remainingTime));
            }
        }

        // Timeout atteint
        $errorMessage = sprintf('❌ Timeout: Le service %s n\'est pas devenu opérationnel après %d secondes', $serviceName, $timeoutSeconds);
        $this->mercureService->dispatch(
            message: $errorMessage,
            type: TypeLog::ERROR,
            level: Level::Error
        );

        throw new ProcessFailedException(new Process($command));
    }
}