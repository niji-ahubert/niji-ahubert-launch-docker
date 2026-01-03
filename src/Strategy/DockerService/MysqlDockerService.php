<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ServiceContainer;
use App\Model\Service\AbstractContainer;

final readonly class MysqlDockerService extends AbstractDatabaseDockerService
{
    public function support(AbstractContainer $service): bool
    {
        return ServiceContainer::MYSQL === $service->getServiceContainer();
    }

    public function getDsnProtocol(): string
    {
        return ServiceContainer::MYSQL->value;
    }

    protected function getServiceContainer(): ServiceContainer
    {
        return ServiceContainer::MYSQL;
    }
}
