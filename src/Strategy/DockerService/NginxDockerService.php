<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ServiceContainer;
use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Util\DockerComposeUtility;

final readonly class NginxDockerService extends AbstractDockerService
{
    public function support(AbstractContainer $service): bool
    {
        return ServiceContainer::NGINX === $service->getServiceContainer();
    }

    #[\Override]
    public function getDefaultPorts(AbstractContainer $service): array
    {
        return ['9000'];
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        $dependsOn = DockerComposeUtility::getContainerWebserver($project, WebServer::NGINX);

        $serviceSkeleton = [
            'image' => \sprintf('%s:%s', ServiceContainer::NGINX->getValue(), $service->getDockerVersionService()),
            'container_name' => \sprintf('%s_service', ServiceContainer::NGINX->getValue()),
            'volumes' => [
                \sprintf('%s:/data', $volumeName),
                \sprintf('%s:/etc/nginx/conf.d/', str_replace(FileSystemEnvironmentServices::PROJECT_ROOT_FOLDER_IN_DOCKER, '${WSL_PATH_FOLDER_PROJECTS_ROOT}', $this->fileSystemEnvironmentServices->getProjectDockerFolderWebserver($project, WebServer::NGINX))),
                ...$this->extractProjectVolume($project),
            ],
            'profiles' => ['runner-dev'],
            'networks' => ['traefik'],
        ];

        if ([] !== $dependsOn) {
            $serviceSkeleton['depends_on'] = $dependsOn;
        }

        $serviceSkeleton['labels'] = [
            'traefik.enable=true',
            'traefik.docker.network=public-dev',
            \sprintf('traefik.http.routers.%s-%s-nginx.tls=true', $project->getClient(), $project->getProject()),
            ...$this->extractUrl($project),
        ];

        return $serviceSkeleton;
    }

    /**
     * @return string[]
     */
    private function extractProjectVolume(Project $project): array
    {
        $volumes = [];

        foreach ($project->getServiceContainer() as $container) {
            if ($container->getServiceContainer() instanceof ServiceContainer) {
                continue;
            }
            $volumes[] = \sprintf('%s:%s', str_replace(FileSystemEnvironmentServices::PROJECT_ROOT_FOLDER_IN_DOCKER, '${WSL_PATH_FOLDER_PROJECTS_ROOT}', $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $container)), $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $container));
        }

        return $volumes;
    }

    /**
     * @return string[]
     */
    private function extractUrl(Project $project): array
    {
        $hosts = [];

        foreach ($project->getServiceContainer() as $container) {
            if ($container->getServiceContainer() instanceof ServiceContainer) {
                continue;
            }
            if (WebServer::NGINX === $container->getWebServer()?->getWebServer()) {
                $hosts[] = \sprintf('Host(`%s`)', $container->getUrlService());
            }
        }

        if ([] === $hosts) {
            return [];
        }

        return [
            \sprintf(
                'traefik.http.routers.%s-%s-nginx.rule=%s',
                $project->getClient(),
                $project->getProject(),
                implode(' || ', $hosts),
            ),
        ];
    }
}
