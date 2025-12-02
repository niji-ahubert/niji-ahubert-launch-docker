<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Start;

use App\Enum\ApplicationStep;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\Step\AbstractServiceStepHandler;
use App\Strategy\Step\AbstractStartServiceStepHandler;

final readonly class NpmServiceStepHandler extends AbstractStartServiceStepHandler
{
    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {


        $this->mercureService->dispatch(
            message: '📦 Configuration des dépendances npm',
            type: TypeLog::START
        );

        $this->mercureService->dispatch(
            message: 'Les dépendances npm seront installées lors du build Docker'
        );

        $this->mercureService->dispatch(
            message: '✅ Configuration npm préparée',
            type: TypeLog::COMPLETE,
            exitCode: 0
        );

    }

    public static function getPriority(): int
    {
        return 3;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::NPM;
    }
}
