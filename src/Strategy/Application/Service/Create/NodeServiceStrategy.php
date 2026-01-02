<?php

declare(strict_types=1);

namespace App\Strategy\Application\Service\Create;

use App\Enum\ApplicationStep;
use App\Enum\ContainerType\ProjectContainer;
use App\Enum\DockerAction;
use App\Model\Service\AbstractContainer;
use App\Strategy\Application\Service\AbstractServiceStrategy;

/**
 * Stratégie de création de projet Node.js.
 *
 * Cette stratégie initialise un projet Node.js minimal avec package.json requis pour Docker
 */
final readonly class NodeServiceStrategy extends AbstractServiceStrategy
{
    public function getSteps(): array
    {
        return [
            ApplicationStep::INIT_GITIGNORE,
            ApplicationStep::NODE_INIT,
            ApplicationStep::NPM,
            ApplicationStep::ACCESS_RIGHT,
        ];
    }

    public function supports(AbstractContainer $serviceContainer, DockerAction $dockerAction): bool
    {
        return ProjectContainer::NODE === $serviceContainer->getServiceContainer()
            && DockerAction::BUILD === $dockerAction;
    }
}
