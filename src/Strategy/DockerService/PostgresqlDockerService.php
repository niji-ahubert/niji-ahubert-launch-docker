<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ServiceContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\DockerCompose\DockerComposeFile;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PostgresqlDockerService extends AbstractDockerService
{
    public function __construct(
        #[Autowire(param: 'bdd.root_password')]
        private string                $rootPassword,
        #[Autowire(param: 'bdd.database')]
        private string                $database,
        #[Autowire(param: 'bdd.user')]
        private string                $dbUser,
        DockerComposeFile             $dockerComposeFile,
        Generator                     $makerGenerator,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
    )
    {
        parent::__construct($dockerComposeFile, $makerGenerator, $fileSystemEnvironmentServices);
    }


    public function support(AbstractContainer $service): bool
    {
        return ServiceContainer::PGSQL === $service->getServiceContainer();
    }

    #[\Override]
    protected function getDefaultPorts(AbstractContainer $service): array
    {
        return ['5432'];
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {

        return [
            'image' => \sprintf('%s:%s', ServiceContainer::PGSQL->getValue(), $service->getDockerVersionService()),
            'container_name' => sprintf('%s_service_database', ServiceContainer::PGSQL->getValue()),
            'profiles' => ['runner-dev'],
            'networks' => ['traefik'],
            'volumes' => [sprintf('%s:/var/lib/postgresql/data', $volumeName)],
            'environment' => [
                'POSTGRES_PASSWORD' => $this->rootPassword,
                'POSTGRES_USER' => $this->dbUser,
                'POSTGRES_DB' => $this->database,
            ],
        ];
    }
}
