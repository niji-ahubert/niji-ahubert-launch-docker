<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use App\Strategy\Step\AbstractServiceStepHandler;
use Monolog\Level;
use Symfony\Component\Filesystem\Filesystem;

final readonly class FolderProjectCreateServiceStepHandler extends AbstractBuildServiceStepHandler
{

    public function __construct(
        private Filesystem            $filesystem,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService                $mercureService,
        ProcessRunnerService          $processRunner,
        private string                $projectDir
    )
    {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public static function getPriority(): int
    {
        return 5;
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {

        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer);

        if ($this->fileSystemEnvironmentServices->isDirectoryEmpty($applicationProjectPath) === false) {
            $this->mercureService->dispatch(
                message: sprintf('Le dossier %s n\'est pas vide, opération annulée', $applicationProjectPath),
                level: Level::Warning
            );
            return;
        }

        if (!$this->filesystem->exists($applicationProjectPath) && null === $serviceContainer->getGithubRepository()) {

            $cmd = ['mkdir', '-p', $applicationProjectPath];

            $this->processRunner->run($cmd, '📁 Initialisation du dossier ', $this->projectDir);


        }
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::INIT_FOLDER_REPOSITORY;
    }

}
