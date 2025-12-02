<?php

declare(strict_types=1);

namespace App\Strategy\EnvVariable;

use App\Model\Project;
use App\Model\Service\AbstractContainer;

/**
 * Classe abstraite pour les stratégies de génération de variables d'environnement
 * Contient les variables communes à tous les types de services.
 */
abstract class AbstractEnvVariableStrategy implements EnvVariableStrategyInterface
{
    /**
     * Génère les variables d'environnement communes.
     *
     * @param AbstractContainer $serviceContainer Le conteneur de service
     * @param Project $project Le projet
     *
     * @return array<string, int|string> Les variables d'environnement communes
     */
    protected function getCommonVariables(AbstractContainer $serviceContainer, Project $project): array
    {
        $framework = $serviceContainer->getFramework();
        $webServer = $serviceContainer->getWebServer();

        return [
            'PROJECT' => $project->getProject(),
            'CLIENT' => $project->getClient(),
            'DOCKER_ENV' => $project->getEnvironmentContainer()->value,
            'TRAEFIK_NETWORK' => $project->getTraefikNetwork(),
            'FOLDER_NAME' => $serviceContainer->getFolderName() ?? '',
            'SERVICE_TYPE' => $serviceContainer->getServiceContainer()->value,
            'URL_LOCAL_WEBSITE' => $serviceContainer->getUrlService() ?? '',
            'ENABLE_LOCAL_SERVER' => $webServer?->getWebServer()->value ?? '',
            'PORT_NUMBER' => $webServer?->getPortWebServer() ?? 9000,
            'INDEX_FOLDER' => $framework?->getFolderIndex() ?? '',
            'INSTALL_QUALITY_TOOLS' => $framework?->isHasQualityTools() ? 'true' : 'false',
            'SHOULD_USE_COMPOSER' => $framework?->isUseComposer() ? 'true' : 'false',
            'FRAMEWORK' => $framework?->getName()->value ?? '',
            'FRAMEWORK_VERSION' => $framework?->getFrameworkVersion() ?? '',
           
        ];
    }
}
