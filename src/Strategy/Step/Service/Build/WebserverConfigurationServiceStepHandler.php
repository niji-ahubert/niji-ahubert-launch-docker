<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;


use App\Enum\ApplicationStep;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use App\Strategy\Step\AbstractServiceStepHandler;
use App\Strategy\Webserver\abstractConfiguration;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class WebserverConfigurationServiceStepHandler extends AbstractBuildServiceStepHandler
{
    /**
     * @param iterable<abstractConfiguration> $webserverConfigurations
     */
    public function __construct(
        #[AutowireIterator(abstractConfiguration::APP_STEP_HANDLER)]
        private iterable              $webserverConfigurations,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService                $mercureService,
        ProcessRunnerService          $processRunner,
    )
    {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }


    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        if ($serviceContainer->getServiceContainer() instanceof ServiceContainer) {
            return;
        }

        foreach ($this->webserverConfigurations as $webserverConfiguration) {

            if (!$webserverConfiguration->support($serviceContainer)) {
                continue;
            }
            $this->mercureService->dispatch(
                message: sprintf('⚙️ Configuration du serveurs web %s', $serviceContainer->getWebServer()?->getWebServer()->getValue()),
                type: TypeLog::START
            );

            ($webserverConfiguration)($project, $serviceContainer);

            $this->mercureService->dispatch(
                message: sprintf('✅ Configuration %s générée pour %s', $serviceContainer->getWebServer()?->getWebServer()->getValue(), $serviceContainer->getFolderName()),
                type: TypeLog::COMPLETE,
                exitCode: 0
            );
        }

    }


    public static function getPriority(): int
    {
        return 0;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::CONFIGURATION_WEBSERVER;
    }
}
