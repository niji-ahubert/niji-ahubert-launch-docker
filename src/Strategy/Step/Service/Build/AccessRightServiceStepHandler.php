<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Enum\ContainerType\ServiceContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;

final class AccessRightServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService $mercureService,
        ProcessRunnerService $processRunner,
        private readonly ProcessRunnerService $processRunnerService,
    ) {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        if ($serviceContainer->getServiceContainer() instanceof ServiceContainer) {
            return;
        }

        $cmdShell = 'find . -path ./vendor -prune -o -type d -exec chmod 775 {} + && find . -path ./vendor -prune -o -type f -exec chmod 664 {} +';
        $command = [
            'sh', '-c', $cmdShell,
        ];

        $this->processRunnerService->run($command, '📦 fix Access Right', $this->fileSystemEnvironmentServices->getPathClient($project->getClient()));
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::ACCESS_RIGHT;
    }
}
