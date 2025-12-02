<?php

declare(strict_types=1);

namespace App\Strategy\EnvVariable;

use App\Enum\ContainerType\ProjectContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

class NodeEnvVariableStrategy extends AbstractEnvVariableStrategy
{
    /**
     * @throws \Exception
     */
    public function generateSocleEnvVariables(AbstractContainer $serviceContainer, Project $project): array
    {
        return [
            'NODE_VERSION' => $serviceContainer->getDockerVersionService() ?? throw new \Exception('Version de Docker non définie'),
            'DOCKER_ENV' => $project->getEnvironmentContainer()->value,
        ];

    }

    /**
     * @return array<string, int|string>
     */
    public function generateVariables(AbstractContainer $serviceContainer, Project $project): array
    {
        $commonVariables = $this->getCommonVariables($serviceContainer, $project);
        $nodeVariables = [
            'NODE_VERSION' => $serviceContainer->getDockerVersionService() ?? 'latest',
            'TAG_VERSION' => 'latest',
        ];

        return array_merge($commonVariables, $nodeVariables);
    }

    public function supports(AbstractContainer $serviceContainer): bool
    {
        return ProjectContainer::NODE === $serviceContainer->getServiceContainer();
    }
}
