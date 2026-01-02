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
 * Stratégie de création de projet PHP vanilla.
 *
 * Cette stratégie initialise un projet PHP de base avec composer.json
 */
final readonly class PhpServiceStrategy extends AbstractServiceStrategy
{
    public function getSteps(): array
    {
        return [
            ApplicationStep::INIT_GITIGNORE,
            ApplicationStep::COMPOSER_INIT,
            ApplicationStep::COMPOSER,
            ApplicationStep::ACCESS_RIGHT,
            ApplicationStep::CONFIGURATION_WEBSERVER,
        ];
    }

    public function supports(AbstractContainer $serviceContainer, DockerAction $dockerAction): bool
    {
        return ProjectContainer::PHP === $serviceContainer->getServiceContainer()
            && FrameworkLanguagePhp::PHP === $serviceContainer->getFramework()?->getName()
            && DockerAction::BUILD === $dockerAction;
    }
}
