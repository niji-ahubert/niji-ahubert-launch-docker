<?php

declare(strict_types=1);

namespace App\Services\Generation;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Util\DockerUtility;
use App\Util\EnvVarUtility;
use Monolog\Level;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Webmozart\Assert\Assert;

/**
 * Service pour arrêter les projets Docker sans supprimer les volumes.
 *
 * Arrête et supprime les containers Docker d'un projet en utilisant docker compose down --remove-orphans.
 * Les volumes persistent (pas d'option --volumes) pour permettre un redémarrage ultérieur avec les données intactes.
 *
 * Les logs sont sauvegardés dans projects/{client}/{project}/logs/stop.log
 */
final readonly class DeleteProjectService
{
    public function __construct(
        private FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private MercureService                $mercureService,
        private string                        $projectDir,
        private ProcessRunnerService          $processRunnerService,
        private StopProjectService            $stopProjectService
    )
    {
    }


    public function deleteProject(Project $project): void
    {
        $this->stopProjectService->stopProject($project);

        $this->mercureService->initialize($project, LoggerChannel::DELETE);

        $this->mercureService->dispatch(
            message: sprintf('📦 Suppression du projet %s %s', $project->getClient(), $project->getProject()),
            type: TypeLog::START,
        );


        foreach ($project->getServiceContainer() as $service) {
            try {

                match (true) {
                    $service->getServiceContainer() instanceof ProjectContainer => $this->deleteService($project, $service),
                    default => null,
                };

            } catch (ProcessFailedException $e) {

                $this->mercureService->dispatch(
                    message: sprintf('❌ Échec du démarrage pour le service: %s', $service->getFolderName()),
                    type: TypeLog::ERROR,
                    level: Level::Error,
                    error: $e->getMessage()
                );
            }
        }
        $pathProject = $this->fileSystemEnvironmentServices->getPathProject($project);
        Assert::string($pathProject);
        $command = [
            'rm',
            '-R',
            $pathProject
        ];


        $this->processRunnerService->run(
            $command,
            sprintf('Suppression repertoire %s', $pathProject),
            $this->projectDir
        );


    }


    private function deleteService(Project $project, AbstractContainer $service): void
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
            'image',
            'rm',
            $imageName
        ];


        $this->processRunnerService->run(
            $command,
            sprintf('Delete docker Image %s', $service->getFolderName()),
            $this->projectDir,
            env: EnvVarUtility::loadEnvironmentVariables($this->fileSystemEnvironmentServices->getComponentEnvFile($project, $service))
        );


    }


}
