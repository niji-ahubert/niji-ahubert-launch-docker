<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\DataStorage;
use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use App\Enum\WebServer;
use App\Form\Model\ServiceProjectModel;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Model\Service\AbstractFramework;
use App\Services\Form\FrameworkServices;
use App\Services\Form\WebServerServices;
use App\Services\StrategyManager\ContainerServices;
use Webmozart\Assert\Assert;

readonly class ServiceProjectModelToProjectTransformer
{
    public function __construct(
        private ContainerServices $containerServices,
        private FrameworkServices $frameworkServices,
        private WebServerServices $webServerServices,
    )
    {
    }

    public function transform(ServiceProjectModel $model): AbstractContainer
    {
        Assert::isInstanceOf($model->getLanguage(), ProjectContainer::class);
        $projectContainer = $this->containerServices->getServiceContainer($model->getLanguage()->value);
        if (!$projectContainer instanceof AbstractContainer) {
            throw new \InvalidArgumentException('Service non trouvé');
        }
        $versionService = $model->getVersionService();
        Assert::isInstanceOf($versionService, VersionServiceSupportedInterface::class);
        Assert::string($versionService->getValue());
        $projectContainer->setDockerVersionService($versionService->getValue());
        $projectContainer->setDockerServiceName(\sprintf('%s_%s', $model->getFolderName(), $model->getLanguage()->value));
        if ($model->getFramework() instanceof FrameworkLanguageInterface) {
            $framework = $this->frameworkServices->getServiceFramework($model->getFramework()->getValue());
            Assert::isInstanceOf($framework, AbstractFramework::class);
            $projectContainer->setFramework($framework);

            $versionFramework = $model->getVersionFramework();

            if ($versionFramework instanceof VersionFrameworkSupportedInterface) {
                $framework->setFrameworkVersion($versionFramework->getValue());
            }

        }

        $projectContainer->setFolderName($model->getFolderName());
        $projectContainer->setExtensionsRequired($model->getExtensionsRequired());
        $projectContainer->setUrlService($model->getUrlService());
        $projectContainer->setWebServer($this->webServerServices->getWebserver($model));
        $projectContainer->setGithubRepository($model->getGithubRepository());
        $projectContainer->setGithubBranch($model->getGithubBranch());
        $projectContainer->setUrlService($model->getUrlService());
        $projectContainer->setFolderName($model->getFolderName());
        $projectContainer->setId($model->getId());
        $projectContainer->setDataStorages($model->getDataStorages());

        return $projectContainer;
    }

    public function reverseTransform(Project $project, string $uuid): ServiceProjectModel
    {
        $containers = $project->getServiceContainer();
        $model = new ServiceProjectModel();
        /** @var AbstractContainer $container */
        $container = current(array_filter(
            $containers,
            static fn(AbstractContainer $container): bool => $container->getId()->toRfc4122() === $uuid,
        ));

        if ($container) {
            $model->setLanguage(ProjectContainer::tryFrom($container->getName()));

            // Version du service
            Assert::isInstanceOf($container->getVersionServiceEnum(), VersionServiceSupportedInterface::class);
            $model->setVersionService($container->getVersionServiceEnum());

            $framework = $container->getFramework();
            if ($framework) {
                /** @phpstan-var FrameworkLanguageInterface<FrameworkLanguagePhp|FrameworkLanguageNode> $frameworkName */
                $frameworkName = $framework->getName();
                $model->setFramework($frameworkName);

                if ($framework->getVersionFrameworkEnum() instanceof VersionFrameworkSupportedInterface) {
                    $model->setVersionFramework($framework->getVersionFrameworkEnum());
                }

            }
            $model->setId($container->getId());
            $model->setFolderName($container->getFolderName());
            $model->setExtensionsRequired($container->getExtensionsRequired());
            $model->setUrlService($container->getUrlService());
            $webServerEnum = $container->getWebServer()?->getWebServer();
            $model->setWebServer($webServerEnum);
            $model->setGithubRepository($container->getGithubRepository());
            $model->setGithubBranch($container->getGithubBranch());
            $model->setDataStorages($container->getDataStorages());
        }

        return $model;
    }
}
