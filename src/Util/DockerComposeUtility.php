<?php

declare(strict_types=1);

namespace App\Util;

use App\Enum\ContainerType\ServiceContainer;
use App\Enum\WebServerPhp;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

final readonly class DockerComposeUtility
{
    public static function getProjectServiceName(Project $project, AbstractContainer $service): string
    {
        return \sprintf('%s-%s-%s-%s', $project->getClient(), $project->getProject(), $service->getFolderName(), $project->getEnvironmentContainer()->value);
    }

    /**
     * @return string[]
     */
    public static function getContainerWebserver(Project $project, WebServerPhp $webServer): array
    {
        $services = [];

        foreach ($project->getServiceContainer() as $container) {
            if ($container->getServiceContainer() instanceof ServiceContainer) {
                continue;
            }

            if ($container->getWebServer()?->getWebServer() === $webServer) {
                $services[] = self::getProjectServiceName($project, $container);
            }
        }

        return $services;
    }
}
