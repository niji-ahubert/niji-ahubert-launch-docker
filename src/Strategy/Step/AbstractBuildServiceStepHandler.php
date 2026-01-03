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
        protected readonly ?string $wslPathFolderSocleRoot = null,
        protected readonly ?string $wslPathFolderProjectsRoot = null,
    ) {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public static function getDockerAction(): DockerAction
    {
        return DockerAction::BUILD;
    }

    protected function resolveHostPath(string $containerPath): string
    {
        if (null === $this->wslPathFolderSocleRoot || null === $this->wslPathFolderProjectsRoot) {
            throw new \RuntimeException('Project root configuration is missing');
        }

        $internalProjectsPath = FileSystemEnvironmentServices::PROJECT_ROOT_FOLDER_IN_DOCKER;

        if (str_starts_with($containerPath, $internalProjectsPath)) {
            $relativePath = substr($containerPath, \strlen($internalProjectsPath));
            $hostBase = $this->wslPathFolderProjectsRoot;

            if (!str_starts_with($hostBase, '/')) {
                $hostBase = \sprintf('%s/%s', $this->wslPathFolderSocleRoot, $hostBase);
            }

            return \sprintf('%s%s', $hostBase, $relativePath);
        }

        return str_replace(FileSystemEnvironmentServices::DOCKER_ROOT_DIRECTORY, $this->wslPathFolderSocleRoot, $containerPath);
    }
}
