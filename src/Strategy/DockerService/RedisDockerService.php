<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ServiceContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

final readonly class RedisDockerService extends AbstractDockerService
{
    public function support(AbstractContainer $service): bool
    {
        return ServiceContainer::REDIS === $service->getServiceContainer();
    }

    #[\Override]
    public function getDefaultPorts(AbstractContainer $service): array
    {
        return ['6379'];
    }

    protected function getServiceContainer(): ServiceContainer
    {
        return ServiceContainer::REDIS;
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        return [
            'image' => \sprintf('%s:%s', ServiceContainer::REDIS->getValue(), $service->getDockerVersionService()),
            'container_name' => \sprintf('%s_%s_%s_service', $project->getClient(), $project->getProject(), $this->getServiceContainer()->value),
            'volumes' => [\sprintf('%s:/data', $volumeName)],
            'profiles' => ['runner-dev'],
            'networks' => ['traefik'],
        ];
    }
}
