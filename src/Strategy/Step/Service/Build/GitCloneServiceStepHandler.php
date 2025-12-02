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

final readonly class GitCloneServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public const MAIN_GIT_BRANCH = 'main';

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
        return 6;
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {

        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer);


        if (!$this->filesystem->exists($applicationProjectPath) && null !== $serviceContainer->getGithubRepository()) {

            if ($this->fileSystemEnvironmentServices->isDirectoryEmpty($applicationProjectPath) === false) {
                $this->mercureService->dispatch(
                    message: sprintf('Le dossier %s n\'est pas vide, opération annulée', $applicationProjectPath),
                    level: Level::Warning
                );
                return;
            }

            $repository = $serviceContainer->getGithubRepository();
            $branch = $serviceContainer->getGithubBranch() ?? self::MAIN_GIT_BRANCH;

            $message = \sprintf('📥 Clonage du dépôt: %s (branche: %s)', $repository, $branch);

            $cmd = ['git', 'clone', '--branch', $branch, $repository, $applicationProjectPath];

            $this->processRunner->run($cmd, $message, $this->projectDir);


        }
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::GIT_CLONE;
    }
}
