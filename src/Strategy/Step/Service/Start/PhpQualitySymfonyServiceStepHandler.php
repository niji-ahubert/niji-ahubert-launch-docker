<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Start;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\Step\AbstractStartServiceStepHandler;

final readonly class PhpQualitySymfonyServiceStepHandler extends AbstractStartServiceStepHandler
{

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        
        $installCmd = [
            'composer',
            'require',
            '--dev',
            '--no-install',
            'phpstan/phpstan-symfony',
        ];

        $this->executeInContainer($project, $serviceContainer, $installCmd, '📦 Ajout des composants de qualités');

    }

    public static function getPriority(): int
    {
        return 1;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::PHP_QUALITY_SYMFONY;
    }
}
