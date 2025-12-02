<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Environment;
use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

final readonly class PhpDockerService extends AbstractDockerService
{
    public function support(AbstractContainer $service): bool
    {
        return ProjectContainer::PHP === $service->getServiceContainer();
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        $configPhp = [
            'extends' => [
                'file' => \sprintf('../../../resources/docker-compose/%s.docker-compose.yml', $service->getServiceContainer()->value),
                'service' => \sprintf('%s-%s', $service->getServiceContainer()->value, $project->getEnvironmentContainer()->value),
            ],
        ];

        if ($service->getWebServer() !== null && $service->getWebServer()->getWebServer() !== WebServer::LOCAL) {
            $configPhp['labels'] = ['traefik.enable=false']; //si le webserver est ds un autre container on desactive treafik
        }

        return $configPhp;
    }
}
