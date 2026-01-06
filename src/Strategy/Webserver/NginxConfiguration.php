<?php

declare(strict_types=1);

namespace App\Strategy\Webserver;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\WebServerPhp;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Util\DockerComposeUtility;

final class NginxConfiguration extends abstractConfiguration
{
    public function __invoke(Project $project, AbstractContainer $serviceContainer): void
    {
        $webserverConfigPath = $this->fileSystemEnvironmentServices->getApplicationNginxConfigPath($project, $serviceContainer);

        if ($this->filesystem->exists($webserverConfigPath)) {
            $this->filesystem->remove($webserverConfigPath);
        }

        $this->makerGenerator->generateFile(
            targetPath: $webserverConfigPath,
            templateName: $this->fileSystemEnvironmentServices->getNginxSkeletonFile(),
            variables: [
                'websiteUrl' => $serviceContainer->getUrlService(),
                'dockerServiceName' => DockerComposeUtility::getProjectServiceName($project, $serviceContainer),
                'rootFolder' => $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer),
            ],
        );
        $this->makerGenerator->writeChanges();
    }

    public function support(AbstractContainer $serviceContainer): bool
    {
        return ProjectContainer::PHP === $serviceContainer->getServiceContainer()
            && WebServerPhp::NGINX === $serviceContainer->getWebServer()?->getWebServer();
    }
}
