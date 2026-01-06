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
use Monolog\Level;

final class NextCreateServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService $mercureService,
        ProcessRunnerService $processRunner,
        string $hostUid,
        string $hostGid,
        string $wslPathFolderSocleRoot,
        string $wslPathFolderProjectsRoot,
    ) {
        parent::__construct(
            $fileSystemEnvironmentServices,
            $mercureService,
            $processRunner,
            $hostUid,
            $hostGid,
            $wslPathFolderSocleRoot,
            $wslPathFolderProjectsRoot,
        );
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer);

        if (false === $this->fileSystemEnvironmentServices->isDirectoryEmpty($applicationProjectPath)) {
            $this->mercureService->dispatch(
                message: \sprintf('Le dossier %s n\'est pas vide, l\'opération Création du projet Next.js est annulée', $applicationProjectPath),
                level: Level::Warning,
            );

            return;
        }

        $nodeVersion = $serviceContainer->getDockerVersionService() ?? '20';
        $nextVersion = $serviceContainer->getFramework()?->getFrameworkVersion() ?? 'latest';

        $command = [
            'docker',
            'run',
            '--rm',
            '--volume', \sprintf('%s:/app', $this->resolveHostPath($applicationProjectPath)),
            '--workdir', '/app',
            \sprintf('node:%s-alpine', $nodeVersion),
            'sh',
            '-c',
            \sprintf('npx create-next-app@%s . --typescript --tailwind --eslint --app --no-src-dir --import-alias "@/*" --no-turbopack --yes && chown -R %s:%s /app', $nextVersion, $this->hostUid, $this->hostGid),
        ];

        $this->processRunner->run(
            $command,
            '⚙️ Création du projet Next.js',
            $applicationProjectPath,
        );
    }

    public static function getPriority(): int
    {
        return 4;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::NEXT_CREATE;
    }
}
