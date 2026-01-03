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

/**
 * Classe abstraite pour les services Docker de base de données.
 * Mutualise les propriétés et getters communs à tous les services de BDD.
 */
abstract readonly class AbstractDatabaseDockerService extends AbstractDockerService implements DatabaseDockerServiceInterface
{
    public function __construct(
        #[Autowire(param: 'bdd.root_password')]
        protected string $rootPassword,
        #[Autowire(param: 'bdd.database')]
        protected string $database,
        #[Autowire(param: 'bdd.user')]
        protected string $dbUser,
        #[Autowire(param: 'bdd.password')]
        protected string $dbPassword,
        DockerComposeFile $dockerComposeFile,
        Generator $makerGenerator,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
    ) {
        parent::__construct($dockerComposeFile, $makerGenerator, $fileSystemEnvironmentServices);
    }

    public function getConnectionPassword(): string
    {
        return $this->dbPassword;
    }

    public function getConnectionUser(): string
    {
        return $this->dbUser;
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    public function getDatabasePassword(): string
    {
        return $this->rootPassword;
    }

    public function getDatabaseUser(): string
    {
        return $this->dbUser;
    }

    public function getDatabaseRootPassword(): string
    {
        return $this->rootPassword;
    }

    #[\Override]
    public function getDefaultPorts(AbstractContainer $service): array
    {
        return ['3306'];
    }

    abstract protected function getServiceContainer(): ServiceContainer;

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        $serviceContainer = $this->getServiceContainer();

        return [
            'image' => \sprintf('%s:%s', $serviceContainer->getValue(), $service->getDockerVersionService()),
            'container_name' => \sprintf('%s_service_database', $serviceContainer->getValue()),
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
