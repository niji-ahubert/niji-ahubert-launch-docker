<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ServiceContainer;
use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\DockerCompose\DockerComposeFile;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;

final readonly class NginxDockerService extends AbstractDockerService
{
    
    public function support(AbstractContainer $service): bool
    {
        return ServiceContainer::NGINX === $service->getServiceContainer();
    }

    #[\Override]
    protected function getDefaultPorts(AbstractContainer $service): array
    {
        return ['9000'];
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {

        return [
            'image' => \sprintf('%s:%s', ServiceContainer::NGINX->getValue(), $service->getDockerVersionService()),
            'container_name' => sprintf('%s_service', ServiceContainer::NGINX->getValue()),
            'volumes' => [
                sprintf('%s:/data', $volumeName),
                sprintf('%s:/etc/nginx/conf.d/', str_replace(FileSystemEnvironmentServices::PROJECT_IN_GENERATOR_ROOT_DIRECTORY, '${PROJECT_ROOT}/projects', $this->fileSystemEnvironmentServices->getProjectDockerFolderWebserver($project, WebServer::NGINX))),
                ...$this->extractProjectVolume($project),
            ],
            'profiles' => ['runner-dev'],
            'networks' => ['traefik'],
            'labels' => [
                'traefik.enable=true',
                'traefik.docker.network=public-dev',
                sprintf('traefik.http.routers.%s-%s-nginx.tls=true', $project->getClient(), $project->getProject()),
                ...$this->extractUrl($project)
            ],
        ];


    }

    private function extractProjectVolume(Project $project): ?array
    {
        $volumes = null;

        foreach ($project->getServiceContainer() as $container) {

            if ($container->getServiceContainer() instanceof ServiceContainer) {
                continue;
            }
            $volumes[] = sprintf('%s:%s', str_replace(FileSystemEnvironmentServices::PROJECT_IN_GENERATOR_ROOT_DIRECTORY, '${PROJECT_ROOT}/projects', $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $container)), $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $container));
        }

        return $volumes;
    }

    private function extractUrl(Project $project): array
    {
        $hosts = [];

        foreach ($project->getServiceContainer() as $container) {
            if ($container->getServiceContainer() instanceof ServiceContainer) {
                continue;
            }
            if ($container->getWebServer()?->getWebServer() === WebServer::NGINX) {
                $hosts[] = sprintf('Host(`%s`)', $container->getUrlService());
            }
        }

        if (empty($hosts)) {
            return [];
        }

        return [
            sprintf(
                'traefik.http.routers.%s-%s-nginx.rule=%s',
                $project->getClient(),
                $project->getProject(),
                implode(' || ', $hosts)
            )
        ];
    }

}
