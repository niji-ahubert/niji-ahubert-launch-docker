<?php

declare(strict_types=1);

namespace App\Strategy\EnvVariable;

use App\Enum\ContainerType\ProjectContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

/**
 * Stratégie pour générer les variables d'environnement spécifiques aux services PHP.
 */
class PhpEnvVariableStrategy extends AbstractEnvVariableStrategy
{

    public function generateSocleEnvVariables(AbstractContainer $serviceContainer, Project $project): array
    {
        return [
            'PHP_VERSION' => $serviceContainer->getDockerVersionService() ?? throw new \Exception('Version de Docker non définie'),
            'DOCKER_ENV' => $project->getEnvironmentContainer()->value,
        ];

    }

    /**
     * @return array<string, int|string>
     * @throws \Exception
     *
     */
    public function generateVariables(AbstractContainer $serviceContainer, Project $project): array
    {
        $commonVariables = $this->getCommonVariables($serviceContainer, $project);

        $phpVariables = [
            'PHP_VERSION' => $serviceContainer->getDockerVersionService() ?? throw new \Exception('Version de Docker non définie'),
            'PHP_EXTENSIONS' => implode(',', $serviceContainer->getExtensionsRequired() ?? []),
            'TAG_VERSION' => 'latest',
        ];

        return array_merge($commonVariables, $phpVariables);
    }

    public function supports(AbstractContainer $serviceContainer): bool
    {
        return ProjectContainer::PHP === $serviceContainer->getServiceContainer();
    }
}
