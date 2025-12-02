<?php

declare(strict_types=1);

namespace App\Services\Generation;

use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Util\DockerUtility;

/**
 * Service pour arrêter les projets Docker sans supprimer les volumes.
 *
 * Arrête et supprime les containers Docker d'un projet en utilisant docker compose down --remove-orphans.
 * Les volumes persistent (pas d'option --volumes) pour permettre un redémarrage ultérieur avec les données intactes.
 *
 * Les logs sont sauvegardés dans projects/{client}/{project}/logs/stop.log
 */
final readonly class StopProjectService
{
    public function __construct(
        private FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private MercureService                $mercureService,
        private string                        $projectDir, private ProcessRunnerService $processRunnerService
    )
    {
    }


    public function stopProject(Project $project): void
    {
        $this->mercureService->initialize($project, LoggerChannel::STOP);

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
            'down',
            '--remove-orphans'
        ];

        $this->processRunnerService->run($command, '📦 Arrêt des services externes', $this->projectDir);

        $completeMessage = 'Arrêt des services terminés avec succès';
        $this->mercureService->dispatch(
            message: $completeMessage,
            type: TypeLog::COMPLETE,
        );

    }


}
