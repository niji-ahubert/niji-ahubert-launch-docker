<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Start;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Strategy\Step\AbstractStartServiceStepHandler;

final readonly class EntrypointServiceStepHandler extends AbstractStartServiceStepHandler
{

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
//        $entrypointPath = sprintf(
//            '%s/%s',
//            $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer),
//            FileSystemEnvironmentServices::BIN_ENTRYPOINT_ADDON_SH
//        );
//
//        $composerCmd = [
//            'sh',
//            '-c',
//            sprintf('echo "Exécution de %s..." && chmod +x %s && %s', $entrypointPath, $entrypointPath, $entrypointPath)
//        ];
//
//        $this->executeInContainer($project, $serviceContainer, $composerCmd, '⚙️ Execution entrypoint');
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::ENTRYPOINT;
    }
}
