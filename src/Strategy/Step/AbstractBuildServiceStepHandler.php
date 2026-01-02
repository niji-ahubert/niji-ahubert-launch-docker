<?php

declare(strict_types=1);

namespace App\Strategy\Step;

use App\Enum\DockerAction;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;

abstract class AbstractBuildServiceStepHandler extends AbstractServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService $mercureService,
        ProcessRunnerService $processRunner,
        protected readonly ?string $hostUid = null,
        protected readonly ?string $hostGid = null,
        protected readonly ?string $projectRoot = null,
        protected readonly ?string $projectsRootHost = null,
    ) {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public static function getDockerAction(): DockerAction
    {
        return DockerAction::BUILD;
    }

    protected function resolveHostPath(string $containerPath): string
    {
        if (null === $this->projectRoot || null === $this->projectsRootHost) {
            throw new \RuntimeException('Project root configuration is missing');
        }

        $internalProjectsPath = FileSystemEnvironmentServices::PROJECT_IN_GENERATOR_ROOT_DIRECTORY;

        if (str_starts_with($containerPath, $internalProjectsPath)) {
            $relativePath = substr($containerPath, \strlen($internalProjectsPath));
            $hostBase = $this->projectsRootHost;

            if (!str_starts_with($hostBase, '/')) {
                $hostBase = \sprintf('%s/%s', $this->projectRoot, $hostBase);
            }

            return \sprintf('%s%s', $hostBase, $relativePath);
        }

        return str_replace(FileSystemEnvironmentServices::GENERATOR_ROOT_DIRECTORY, $this->projectRoot, $containerPath);
    }
}
