<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Start;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\Step\AbstractStartServiceStepHandler;
use Monolog\Level;

final readonly class LaravelCreateServiceStepHandler extends AbstractStartServiceStepHandler
{
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

        $composerCmd = [
            'composer',
            'create-project',
            'laravel/laravel',
            '.',
            '--no-interaction',
        ];

        $this->executeInContainer($project, $serviceContainer, $composerCmd, '⚙️ Création du projet Laravel');


    }

    public static function getPriority(): int
    {
        return 4;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::LARAVEL_CREATE;
    }
}
