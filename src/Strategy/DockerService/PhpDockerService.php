<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\WebServerPhp;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Model\Service\WebServer;

final readonly class PhpDockerService extends AbstractDockerService
{

    public function support(AbstractContainer $service): bool
    {
        return ProjectContainer::PHP === $service->getServiceContainer();
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        $configPhp = $this->getBaseServiceConfig($service, $project);

        $enableTraefik = !($service->getWebServer() instanceof WebServer && WebServerPhp::LOCAL !== $service->getWebServer()->getWebServer());
        $configPhp['labels'] = $this->getTraefikLabels($service, $enableTraefik);

        $dependsOn = $this->getDatabaseDependencies($service, $project);
        if ([] !== $dependsOn) {
            $configPhp['depends_on'] = $dependsOn;
        }

        return $configPhp;
    }
}
