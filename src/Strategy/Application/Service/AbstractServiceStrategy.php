<?php

declare(strict_types=1);

namespace App\Strategy\Application\Service;

use App\Enum\ApplicationStep;
use App\Enum\DockerAction;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\StrategyManager\StepServiceManagerService;
use App\Strategy\Application\Service\Create\CreateApplicationInterface;
use App\Strategy\Application\Service\Start\StartApplicationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Stratégie de création de projet Symfony.
 *
 * Cette stratégie crée un nouveau projet Symfony en utilisant le skeleton officiel
 */
abstract readonly class AbstractServiceStrategy implements CreateApplicationInterface, StartApplicationInterface
{


    public function __construct(
        protected StepServiceManagerService $stepManager
    )
    {
    }

    public function execute(AbstractContainer $serviceContainer, Project $project, DockerAction $dockerAction): void
    {
        $this->stepManager->executeSteps($this->getSteps(), $serviceContainer, $project, $dockerAction);
    }

    /**
     * @return ApplicationStep[]
     */
    abstract public function getSteps(): array;


    abstract public function supports(AbstractContainer $serviceContainer, DockerAction $dockerAction): bool;

}
