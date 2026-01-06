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
 * Stratégie de création de projet NestJS.
 *
 * Cette stratégie crée un nouveau projet NestJS en utilisant le CLI officiel
 */
final readonly class NestServiceStrategy extends AbstractServiceStrategy
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
            ApplicationStep::NEST_CREATE,
            ApplicationStep::NPM,
            ApplicationStep::DOCKERFILE,
            ApplicationStep::ENV_FILE,
            ApplicationStep::ACCESS_RIGHT,
            ApplicationStep::ENV_FILE_APPLICATION,
            ApplicationStep::ENTRYPOINT_ADDON_COPY,
        ];
    }

    public function supports(AbstractContainer $serviceContainer, DockerAction $dockerAction): bool
    {
        return ProjectContainer::NODE === $serviceContainer->getServiceContainer()
            && FrameworkLanguageNode::NEST === $serviceContainer->getFramework()?->getName()
            && DockerAction::BUILD === $dockerAction;
    }
}
