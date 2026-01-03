<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use Monolog\Level;

final class ComposerInitServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        if (false === $this->fileSystemEnvironmentServices->composerAlreadyDefined($project, $serviceContainer)) {
            $this->mercureService->dispatch(
                message: 'Composer est déjà défini',
                level: Level::Warning,
            );

            return;
        }

        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer);
        $projectName = strtolower(basename($applicationProjectPath));
        $containerType = $serviceContainer->getServiceContainer()->value;

        $cmd = [
            'composer',
            'init',
            '--name', \sprintf('niji-%s/%s', $containerType, $projectName),
            '--version', '1.0',
            '--description', \sprintf('socle %s niji', $containerType),
            '--no-interaction',
        ];

        $this->processRunner->run($cmd, 'Initialisation de la cmd Composer init', $applicationProjectPath, ['XDEBUG_MODE' => 'off']);
    }

    public static function getPriority(): int
    {
        return 4;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::COMPOSER_INIT;
    }
}
