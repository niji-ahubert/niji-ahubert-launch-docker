<?php

declare(strict_types=1);

namespace App\Strategy\Application\Service\Create;

use App\Enum\ApplicationStep;
use App\Enum\ContainerType\ProjectContainer;
use App\Enum\DockerAction;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Model\Service\AbstractContainer;
use App\Strategy\Application\Service\AbstractServiceStrategy;

/**
 * Stratégie de création de projet React.
 *
 * Cette stratégie crée un nouveau projet React en utilisant Vite
 */
final readonly class ReactServiceStrategy extends AbstractServiceStrategy
{
    /**
     * @return ApplicationStep[]
     */
    public function getSteps(): array
    {
        return [
            ApplicationStep::INIT_GITIGNORE,
            ApplicationStep::INIT_FOLDER_REPOSITORY,
            ApplicationStep::GIT_CLONE,
            ApplicationStep::REACT_CREATE,
            ApplicationStep::NPM,
            ApplicationStep::DOCKERFILE,
            ApplicationStep::ENV_FILE,
            ApplicationStep::ENV_FILE_APPLICATION,
            ApplicationStep::ACCESS_RIGHT,
        ];
    }

    public function supports(AbstractContainer $serviceContainer, DockerAction $dockerAction): bool
    {
        return ProjectContainer::NODE === $serviceContainer->getServiceContainer()
            && FrameworkLanguageNode::REACT === $serviceContainer->getFramework()?->getName()
            && DockerAction::BUILD === $dockerAction;
    }
}
