<?php

namespace App\Strategy\Webserver;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Util\DockerComposeUtility;

final  class NginxConfiguration extends abstractConfiguration
{


    public function support(AbstractContainer $serviceContainer): bool
    {
        return $serviceContainer->getServiceContainer() === ProjectContainer::PHP
            && $serviceContainer->getWebServer()?->getWebServer() === WebServer::NGINX;
    }

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

}