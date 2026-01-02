<?php

declare(strict_types=1);

namespace App\Strategy\Trait;

use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Util\DockerComposeUtility;
use App\Util\DockerUtility;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Service\Attribute\Required;

trait ExecuteInContainerTrait
{
    protected string $projectDir;
    protected FileSystemEnvironmentServices $fileSystemEnvironmentServices;
    protected MercureService $mercureService;
    protected ProcessRunnerService $processRunner;

    // Ces propriétés sont présumées exister dans la classe utilisatrice ou son parent.
    // Si ce n'est pas le cas, elles seront définies dynamiquement (mais c'est moins propre pour l'IDE).
    // Pour éviter les conflits de définition avec AbstractStepHandler, on ne les re-déclare pas ici
    // explicitement comme propriétés, mais on compte sur les setters pour l'injection.

    #[Required]
    public function setFileSystemEnvironmentServices(FileSystemEnvironmentServices $fileSystemEnvironmentServices): void
    {
        $this->fileSystemEnvironmentServices = $fileSystemEnvironmentServices;
    }

    #[Required]
    public function setMercureService(MercureService $mercureService): void
    {
        $this->mercureService = $mercureService;
    }

    #[Required]
    public function setProcessRunnerService(ProcessRunnerService $processRunner): void
    {
        $this->processRunner = $processRunner;
    }

    #[Required]
    public function setProjectDir(#[Autowire('%kernel.project_dir%')] string $projectDir): void
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Exécute une commande à l'intérieur du container Docker du service.
     *
     * @param Project           $project    Le projet
     * @param AbstractContainer $service    Le service container
     * @param string[]          $command    La commande à exécuter (ex: ['composer', 'install'])
     * @param string            $logMessage Message de log pour l'exécution
     * @param bool              $rootMode   Exécuter en tant que root
     *
     * @throws ProcessFailedException Si la commande échoue
     */
    protected function executeInContainer(
        Project $project,
        AbstractContainer $service,
        array $command,
        string $logMessage,
        bool $rootMode = false,
    ): int {
        $workdir = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $service);

        $commandShell = \sprintf(
            'cd %s && %s',
            $workdir,
            implode(' ', array_map(escapeshellarg(...), $command)),
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
            'sh', '-c', $commandShell,
        ];

        $exitCode = $this->processRunner->run($dockerCommand, $logMessage, $this->projectDir);

        if (0 !== $exitCode) {
            throw new ProcessFailedException(new Process($dockerCommand));
        }

        return $exitCode;
    }
}
