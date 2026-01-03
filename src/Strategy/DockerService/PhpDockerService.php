<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\DockerCompose\DockerComposeFile;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class PhpDockerService extends AbstractDockerService
{
    /**
     * @param iterable<AbstractDockerService> $dockerServices
     */
    public function __construct(
        #[AutowireIterator('app.docker_service')]
        private iterable $dockerServices,
        DockerComposeFile $dockerComposeFile,
        Generator $makerGenerator,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
    ) {
        parent::__construct($dockerComposeFile, $makerGenerator, $fileSystemEnvironmentServices);
    }

    public function support(AbstractContainer $service): bool
    {
        return ProjectContainer::PHP === $service->getServiceContainer();
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        $configPhp = [
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
            'labels' => [
                \sprintf('traefik.http.routers.${CLIENT}-${PROJECT}-%s.rule=Host(`${URL_LOCAL_WEBSITE}`)', $service->getFolderName()),
                \sprintf('traefik.http.services.${CLIENT}-${PROJECT}-%s.loadbalancer.server.port=${PORT_NUMBER:-9000}', $service->getFolderName()),
                \sprintf('traefik.http.routers.${CLIENT}-${PROJECT}-%s.tls=true', $service->getFolderName()),
            ],
        ];

        if ($service->getWebServer() instanceof \App\Model\Service\WebServer && WebServer::LOCAL !== $service->getWebServer()->getWebServer()) {
            $configPhp['labels'][] = 'traefik.enable=false'; // si le webserver est ds un autre container on desactive treafik
        } else {
            $configPhp['labels'][] = 'traefik.enable=true';
        }

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

        if ([] !== $dependsOn) {
            $configPhp['depends_on'] = $dependsOn;
        }

        return $configPhp;
    }
}
