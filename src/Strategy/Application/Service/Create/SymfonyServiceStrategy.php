<?php

declare(strict_types=1);

namespace App\Strategy\Application\Service\Create;

use App\Enum\ApplicationStep;
use App\Enum\ContainerType\ProjectContainer;
use App\Enum\DockerAction;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Model\Service\AbstractContainer;
use App\Strategy\Application\Service\AbstractServiceStrategy;

/**
 * Stratégie de création de projet Symfony.
 *
 * Cette stratégie crée un nouveau projet Symfony en utilisant le skeleton officiel
 */
final readonly class SymfonyServiceStrategy extends AbstractServiceStrategy
{
    public function getSteps(): array
    {
        return [
            ApplicationStep::INIT_GITIGNORE,
            ApplicationStep::GIT_CLONE,
            ApplicationStep::INIT_FOLDER_REPOSITORY,
            ApplicationStep::SYMFONY_CREATE,
            ApplicationStep::COMPOSER_INIT,
            ApplicationStep::DOCKERFILE,
            ApplicationStep::ENV_FILE,
            ApplicationStep::ACCESS_RIGHT,
            ApplicationStep::CONFIGURATION_WEBSERVER,
            ApplicationStep::PHP_QUALITY,
            ApplicationStep::ENV_FILE_APPLICATION,
        ];
    }

    public function supports(AbstractContainer $serviceContainer, DockerAction $dockerAction): bool
    {
        return ProjectContainer::PHP === $serviceContainer->getServiceContainer()
            && FrameworkLanguagePhp::SYMFONY === $serviceContainer->getFramework()?->getName()
            && DockerAction::BUILD === $dockerAction;
    }
}
