<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ServiceContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

final readonly class PostgresqlDockerService extends AbstractDatabaseDockerService
{
    public function support(AbstractContainer $service): bool
    {
        return ServiceContainer::PGSQL === $service->getServiceContainer();
    }

    #[\Override]
    public function getDefaultPorts(AbstractContainer $service): array
    {
        return ['5432'];
    }

    public function getDsnProtocol(): string
    {
        return ServiceContainer::PGSQL->value;
    }

    #[\Override]
    public function getConnectionPassword(): string
    {
        return $this->rootPassword;
    }

    protected function getServiceContainer(): ServiceContainer
    {
        return ServiceContainer::PGSQL;
    }

    #[\Override]
    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        return [
            'image' => \sprintf('%s:%s', ServiceContainer::PGSQL->getValue(), $service->getDockerVersionService()),
            'container_name' => \sprintf('%s_service_database', ServiceContainer::PGSQL->getValue()),
            'profiles' => ['runner-dev'],
            'networks' => ['traefik'],
            'volumes' => [\sprintf('%s:/var/lib/postgresql/data', $volumeName)],
            'environment' => [
                'POSTGRES_PASSWORD' => $this->rootPassword,
                'POSTGRES_USER' => $this->dbUser,
                'POSTGRES_DB' => $this->database,
            ],
        ];
    }
}
