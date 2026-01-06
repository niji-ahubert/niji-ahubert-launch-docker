<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\DockerCompose\DockerComposeFile;
use App\Services\FileSystemEnvironmentServices;
use App\Util\DockerComposeUtility;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AutoconfigureTag('app.docker_service')]
abstract readonly class AbstractDockerService
{
    /**
     * @param iterable<AbstractDockerService> $dockerServices
     */
    public function __construct(
        private DockerComposeFile $dockerComposeFile,
        private Generator $makerGenerator,
        protected FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        #[AutowireIterator('app.docker_service')]
        protected iterable $dockerServices = [],
    ) {
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $volumeName = \sprintf('my-data-%s-%s-%s', $project->getClient(), $project->getProject(), $serviceContainer->getServiceContainer()->value);
        $service = $this->getServiceSkeleton($volumeName, $serviceContainer, $project);

        $dockerComposeFilePath = $this->fileSystemEnvironmentServices->getDockerComposeFilePath($project);

        if ($serviceContainer->getServiceContainer() instanceof ProjectContainer) {
            $serviceName = DockerComposeUtility::getProjectServiceName($project, $serviceContainer);
        } else {
            $serviceName = $serviceContainer->getDockerServiceName();
        }
        $composeFileManipulator = $this->dockerComposeFile->getDockerComposeFile($project);

        $composeFileManipulator->addDockerService($serviceName, $service);

        if ($serviceContainer->getServiceContainer() instanceof ServiceContainer) {
            $composeFileManipulator->exposePorts($serviceContainer->getDockerServiceName(), $this->getDefaultPorts($serviceContainer));
            $composeFileManipulator->setGlobalVolumeComposeData($volumeName, $serviceContainer->getServiceContainer()->value);
        }

        $this->makerGenerator->dumpFile($dockerComposeFilePath, $composeFileManipulator->getDataString());
        $this->makerGenerator->writeChanges();
    }

    abstract public function support(AbstractContainer $service): bool;

    /**
     * @return string[]
     */
    public function getDefaultPorts(AbstractContainer $service): array
    {
        return [];
    }

    /**
     * @throws RuntimeCommandException
     *
     * @return array<string, array<int|string, string>|string>
     */
    abstract protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array;

    /**
     * @return string[]
     */
    protected function getTraefikLabels(AbstractContainer $service, bool $enableTraefik = true): array
    {
        $labels = [
            \sprintf('traefik.http.routers.${CLIENT}-${PROJECT}-%s.rule=Host(`%s`)', $service->getFolderName(),$service->getUrlService()),
            \sprintf('traefik.http.services.${CLIENT}-${PROJECT}-%s.loadbalancer.server.port=%s', $service->getFolderName(),$service->getWebServer()?->getPortWebServer()),
            \sprintf('traefik.http.routers.${CLIENT}-${PROJECT}-%s.tls=true', $service->getFolderName()),
        ];

        if ($enableTraefik) {
            $labels[] = 'traefik.enable=true';
        } else {
            $labels[] = 'traefik.enable=false';
        }

        return $labels;
    }

    /**
     * @return array<string, array<int|string, string>|string>
     */
    protected function getBaseServiceConfig(AbstractContainer $service, Project $project): array
    {
        return [
            'container_name' => \sprintf('%s-%s-%s-%s', $project->getClient(), $project->getProject(), $service->getFolderName(), $project->getEnvironmentContainer()->value),
            'image' => \sprintf('%s-%s-%s-%s', $project->getClient(), $project->getProject(), $service->getFolderName(), $project->getEnvironmentContainer()->value),
            'profiles' => ['runner-dev'],
            'working_dir' => \sprintf('${PROJECT_ROOT_FOLDER_IN_DOCKER}/%s/%s/%s', $project->getClient(), $project->getProject(), $service->getFolderName()),
            'extends' => [
                'file' => \sprintf('${WSL_PATH_FOLDER_SOCLE_ROOT}/resources/docker-compose/%s.docker-compose.yml', $service->getServiceContainer()->value),
                'service' => \sprintf('%s-%s', $service->getServiceContainer()->value, $project->getEnvironmentContainer()->value),
            ],
            'env_file' => [
                \sprintf('./config/%s.env', $service->getFolderName()),
                \sprintf('./%s/.env.niji-launcher', $service->getFolderName()),
            ],
            'build' => [
                'context' => \sprintf('${PROJECT_ROOT_FOLDER_IN_DOCKER}/%1$s/%2$s/%3$s', $project->getClient(), $project->getProject(), $service->getFolderName()),
            ],
            'volumes' => [
                \sprintf(
                    '${WSL_PATH_FOLDER_PROJECTS_ROOT}/%1$s/%2$s/%3$s:${PROJECT_ROOT_FOLDER_IN_DOCKER}/%1$s/%2$s/%3$s:rw',
                    $project->getClient(),
                    $project->getProject(),
                    $service->getFolderName()
                ),
            ],
        ];
    }

    /**
     * @return string[]
     */
    protected function getDatabaseDependencies(AbstractContainer $service, Project $project): array
    {
        $dependsOn = [];
        
        if (!\in_array($service->getDataStorages(), [null, []], true)) {
            $dataStorageValues = array_map(
                static fn ($ds) => $ds->value,
                $service->getDataStorages(),
            );

            foreach ($project->getServiceContainer() as $container) {
                foreach ($this->dockerServices as $dockerService) {
                    /** @var AbstractDockerService $dockerService */
                    if (
                        $dockerService instanceof DatabaseDockerServiceInterface
                        && $dockerService->support($container)
                        && \in_array($container->getServiceContainer()->value, $dataStorageValues, true)
                    ) {
                        $serviceName = $container->getDockerServiceName();
                        if (null !== $serviceName) {
                            $dependsOn[] = $serviceName;
                        }
                        break;
                    }
                }
            }
        }

        return $dependsOn;
    }
}
