<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Start;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\Step\AbstractStartServiceStepHandler;
use Monolog\Level;

final readonly class SymfonyCreateServiceStepHandler extends AbstractStartServiceStepHandler
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

        $installCmd = [
            'composer',
            'create-project',
            'symfony/skeleton',
            '.',
            '--no-interaction',
        ];
        
        $this->executeInContainer($project, $serviceContainer, $installCmd, '⚙️ Création du projet Symfony');

    }

    public static function getPriority(): int
    {
        return 4;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::SYMFONY_CREATE;
    }
}
