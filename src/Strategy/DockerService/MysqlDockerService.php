<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ServiceContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

final readonly class MysqlDockerService extends AbstractDatabaseDockerService
{
    public function support(AbstractContainer $service): bool
    {
        return ServiceContainer::MYSQL === $service->getServiceContainer();
    }

    #[\Override]
    public function getDefaultPorts(AbstractContainer $service): array
    {
        return ['3306'];
    }

    public function getDsnProtocol(): string
    {
        return ServiceContainer::MYSQL->value;
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        return [
            'image' => \sprintf('%s:%s', ServiceContainer::MYSQL->getValue(), $service->getDockerVersionService()),
            'container_name' => \sprintf('%s_service_database', ServiceContainer::MYSQL->getValue()),
            'profiles' => ['runner-dev'],
            'networks' => ['traefik'],
            'volumes' => [\sprintf('%s:/var/lib/mysql', $volumeName)],
            'environment' => [
                'MYSQL_ROOT_PASSWORD' => $this->rootPassword,
                'MYSQL_DATABASE' => $this->database,
                'MYSQL_USER' => $this->dbUser,
                'MYSQL_PASSWORD' => $this->dbPassword,
            ],
        ];
    }
}
