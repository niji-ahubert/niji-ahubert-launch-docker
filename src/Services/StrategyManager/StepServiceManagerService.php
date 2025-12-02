<?php

declare(strict_types=1);

namespace App\Services\StrategyManager;

use App\Enum\ApplicationStep;
use App\Enum\DockerAction;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\Step\AbstractProjectStepHandler;
use App\Strategy\Step\AbstractServiceStepHandler;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Gestionnaire d'étapes utilisant le pattern Chain of Responsibility.
 */
final readonly class StepServiceManagerService
{
    /**
     * @param iterable<AbstractServiceStepHandler> $stepServiceHandlers
     */
    public function __construct(
        #[AutowireIterator(AbstractServiceStepHandler::APP_STEP_HANDLER, defaultPriorityMethod: 'getPriority')]
        private iterable $stepServiceHandlers,
    )
    {
    }

    /**
     * @param ApplicationStep[] $steps
     */
    public function executeSteps(array $steps, AbstractContainer $serviceContainer, Project $project, DockerAction $dockerAction): void
    {
        foreach ($this->stepServiceHandlers as $handler) {
            if ($handler->supports($steps, $dockerAction)) {
                ($handler)($serviceContainer, $project);
            }
        }
    }
}
