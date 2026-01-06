<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Environment;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

final readonly class NodeDockerService extends AbstractDockerService
{
    public function support(AbstractContainer $service): bool
    {
        return ProjectContainer::NODE === $service->getServiceContainer();
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        $config = $this->getBaseServiceConfig($service, $project);
        $config['labels'] = $this->getTraefikLabels($service);

        $dependsOn = $this->getDatabaseDependencies($service, $project);
        if ([] !== $dependsOn) {
            $config['depends_on'] = $dependsOn;
        }

        return $config;
    }
}
