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

#[AutoconfigureTag('app.docker_service')]
abstract readonly class AbstractDockerService
{
    public function __construct(
        private DockerComposeFile $dockerComposeFile,
        private Generator $makerGenerator,
        protected FileSystemEnvironmentServices $fileSystemEnvironmentServices,
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
}
