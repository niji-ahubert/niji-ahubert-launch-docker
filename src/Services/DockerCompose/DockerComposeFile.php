<?php

declare(strict_types=1);

namespace App\Services\DockerCompose;

use App\Enum\ContainerType\ProjectContainer;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;

final readonly class DockerComposeFile
{
    public function __construct(
        private Filesystem $filesystem,
        private FileSystemEnvironmentServices $environmentServices,
        private DockerComposeFileManipulator $dockerComposeFileManipulator,
        private Generator $makerGenerator,
    ) {
    }

    public function getDockerComposeFile(Project $project, bool $forceCreateNewFile = false): DockerComposeFileManipulator
    {
        $this->environmentServices->loadEnvironments($project);
        $dockerFile = $this->environmentServices->getDockerComposeFilePath($project);

        if (false === $forceCreateNewFile && $this->filesystem->exists($dockerFile)) {
            return $this->getContentDockerComposeFile($dockerFile);
        }

        if ($this->filesystem->exists($dockerFile)) {
            $this->filesystem->remove($dockerFile);
        }

        return $this->createDockerComposeFile($dockerFile, $project);
    }

    private function createDockerComposeFile(string $dockerFile, Project $project): DockerComposeFileManipulator
    {
        $this->dockerComposeFileManipulator->initialize();
        $this->dockerComposeFileManipulator->setGlobalNetworkComposeData($project->getTraefikNetwork());

        $volumeNames = $this->getVolumeNameForProject($project);
        foreach ($volumeNames as $volumeName) {
            $this->dockerComposeFileManipulator->setGlobalVolumeComposeData($volumeName, $volumeName);
        }

        $this->makerGenerator->dumpFile($dockerFile, $this->dockerComposeFileManipulator->getDataString());
        $this->makerGenerator->writeChanges();

        return $this->dockerComposeFileManipulator;
    }

    /**
     * @return array<string>
     */
    private function getVolumeNameForProject(Project $project): array
    {
        foreach ($project->getServiceContainer() as $container) {
            if ($container->getServiceContainer() instanceof ProjectContainer) {
                return match ($container->getServiceContainer()) {
                    ProjectContainer::PHP => ['composer-cache'],
                    ProjectContainer::NODE => ['npm-cache', 'pnpm-cache'],
                };
            }
        }

        return ['composer-cache'];
    }

    private function getContentDockerComposeFile(string $dockerFile): DockerComposeFileManipulator
    {
        $content = file_get_contents($dockerFile);
        if (false === $content) {
            throw new \RuntimeException(\sprintf("Unable to open the file '%s'.", $dockerFile));
        }

        $this->dockerComposeFileManipulator->initialize($content);

        return $this->dockerComposeFileManipulator;
    }
}
