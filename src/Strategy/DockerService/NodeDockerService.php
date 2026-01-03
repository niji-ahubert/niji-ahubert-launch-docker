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
        return [
            'profiles' => ['runner-dev'],
            'extends' => [
                'file' => \sprintf('${WSL_PATH_FOLDER_SOCLE_ROOT}/resources/docker-compose/%s.docker-compose.yml', $service->getServiceContainer()->value),
                'service' => \sprintf('%s-%s', $service->getServiceContainer()->value, Environment::DEV->value),
            ],
        ];
    }
}
