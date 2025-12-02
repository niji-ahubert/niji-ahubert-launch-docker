<?php

declare(strict_types=1);

namespace App\Services\DockerCompose;

use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\DockerService\AbstractDockerService;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class CreateDockerService
{
    /**
     * @param iterable<AbstractDockerService> $servicesDocker
     */
    public function __construct(
        #[AutowireIterator('app.docker_service')]
        private iterable $servicesDocker,
    )
    {
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        foreach ($this->servicesDocker as $serviceDocker) {
            if ($serviceDocker->support($serviceContainer)) {
                ($serviceDocker)($serviceContainer, $project);

                return;
            }
        }
    }
}
