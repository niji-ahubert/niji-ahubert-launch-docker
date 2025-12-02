<?php

declare(strict_types=1);

namespace App\Strategy\Step;

use App\Enum\DockerAction;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Util\DockerComposeUtility;
use App\Util\DockerUtility;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract readonly class AbstractStartServiceStepHandler extends AbstractServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService                $mercureService,
        ProcessRunnerService          $processRunner,
        protected string              $projectDir
    )
    {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public static function getDockerAction(): DockerAction
    {
        return DockerAction::START;
    }

    /**
     * Exécute une commande à l'intérieur du container Docker du service.
     *
     * @param Project $project Le projet
     * @param AbstractContainer $service Le service container
     * @param array $command La commande à exécuter (ex: ['composer', 'install'])
     * @param string $logMessage Message de log pour l'exécution
     * @param bool $rootMode Exécuter en tant que root
     * @return int Le code de sortie de la commande
     */
    protected function executeInContainer(
        Project           $project,
        AbstractContainer $service,
        array             $command,
        string            $logMessage,
        bool              $rootMode = false
    ): int
    {
        $workdir = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $service);

        $commandShell = sprintf(
            'cd %s && %s',
            $workdir,
            implode(' ', array_map('escapeshellarg', $command))
        );

        $dockerCommand = [
            'docker',
            'compose',
            '--profile',
            'runner-dev',
            '--project-name',
            DockerUtility::getProjectName($project),
            '-f',
            $this->fileSystemEnvironmentServices->getDockerComposeFilePath($project),
            'exec',
            '-T',
            ...($rootMode ? ['-u', 'root'] : []),
            DockerComposeUtility::getProjectServiceName($project, $service),
            'sh', '-c', $commandShell
        ];

        $exitCode = $this->processRunner->run($dockerCommand, $logMessage, $this->projectDir);

        if ($exitCode !== 0) {
            throw new ProcessFailedException(new Process($dockerCommand));
        }

        return $exitCode;
    }


}
