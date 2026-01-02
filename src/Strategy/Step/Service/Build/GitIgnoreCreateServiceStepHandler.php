<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Enum\ContainerType\ProjectContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use Monolog\Level;
use Symfony\Component\Filesystem\Filesystem;

final class GitIgnoreCreateServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        private readonly Filesystem $filesystem,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService $mercureService,
        ProcessRunnerService $processRunner,
    ) {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $projectPath = $this->fileSystemEnvironmentServices->getPathProject($project);

        if (null === $projectPath) {
            throw new \RuntimeException('Project path is not defined');
        }

        if (!$this->filesystem->exists($projectPath)) {
            $this->filesystem->mkdir($projectPath);
        }

        $gitignorePath = \sprintf('%s/.gitignore', $projectPath);
        $existingLines = [];

        if ($this->filesystem->exists($gitignorePath)) {
            $fileLines = file($gitignorePath);
            if (false !== $fileLines) {
                $existingLines = array_map(trim(...), $fileLines);
            }
        }

        $newLines = [];
        $containers = $project->getServiceContainer();

        foreach ($containers as $container) {
            if ($container->getServiceContainer() instanceof ProjectContainer && null !== $container->getFolderName()) {
                $folder = $container->getFolderName().'/';
                if (!\in_array($folder, $existingLines, true) && !\in_array($folder, $newLines, true)) {
                    $newLines[] = $folder;
                }
            }
        }

        if ([] !== $newLines) {
            $this->filesystem->appendToFile($gitignorePath, implode(\PHP_EOL, $newLines).\PHP_EOL);

            $this->mercureService->dispatch(
                message: '📄 Mise à jour du .gitignore pour exclure les sous-projets',
                level: Level::Info,
            );
        }
    }

    public static function getPriority(): int
    {
        return 10;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::INIT_GITIGNORE;
    }
}
