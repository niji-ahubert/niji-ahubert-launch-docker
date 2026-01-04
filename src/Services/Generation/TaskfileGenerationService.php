<?php

declare(strict_types=1);

namespace App\Services\Generation;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\Taskfile\TaskfileFile;
use App\Services\Taskfile\TaskfileTaskProviderInterface;
use App\Util\DockerUtility;
use Monolog\Level;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Filesystem\Filesystem;

final readonly class TaskfileGenerationService
{
    /**
     * @param iterable<TaskfileTaskProviderInterface> $taskProviders
     */
    public function __construct(
        private FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private MercureService $mercureService,
        private Generator $makerGenerator,
        private Filesystem $filesystem,
        private TaskfileFile $taskfileFile,
        #[AutowireIterator(TaskfileTaskProviderInterface::class)]
        private iterable $taskProviders,
        private string $wslPathFolderSocleRoot,
        private string $wslPathFolderProjectsRoot,
    ) {
    }

    public function generate(Project $project): void
    {
        $this->mercureService->dispatch(
            message: '📦 Génération du fichier Taskfile.yml',
        );

        try {
            $taskfileConfigPath = $this->fileSystemEnvironmentServices->getProjectTaskFilePath($project);

            if ($this->filesystem->exists($taskfileConfigPath)) {
                $this->filesystem->remove($taskfileConfigPath);
            }

            // Read the template content
            $templateContent = file_get_contents($this->fileSystemEnvironmentServices->getTaskFileSkeletonFile());
            if (false === $templateContent) {
                throw new \RuntimeException('Failed to read Taskfile skeleton template');
            }

            $replacements = [
                '{{WSL_PATH_FOLDER_SOCLE_ROOT}}' => $this->wslPathFolderSocleRoot,
                '{{PROJECT_NAME}}' => DockerUtility::getProjectName($project),
                '{{WSL_PATH_FOLDER_PROJECTS_ROOT}}' => $this->wslPathFolderProjectsRoot,
                '{{PROJECT_ROOT_FOLDER_IN_DOCKER}}' => $this->fileSystemEnvironmentServices::PROJECT_ROOT_FOLDER_IN_DOCKER,
                '{{CLIENT}}' => $project->getClient(),
                '{{PROJECT}}' => $project->getProject(),
            ];

            $generatedContent = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $templateContent,
            );

            $this->filesystem->dumpFile($taskfileConfigPath, $generatedContent);

            $this->addContainerSpecificTasks($project);

            $this->makerGenerator->writeChanges();

            $this->mercureService->dispatch(
                message: '✅ Fichier Taskfile.yml généré avec succès',
            );
        } catch (\Exception $exception) {
            $this->mercureService->dispatch(
                message: '❌ Erreur lors de la génération du Taskfile.yml',
                type: TypeLog::ERROR,
                level: Level::Error,
                error: $exception->getMessage(),
            );
        }
    }

    private function addContainerSpecificTasks(Project $project): void
    {
        $taskfileManipulator = $this->taskfileFile->getTaskfile($project);
        $addedContainerTypes = [];

        foreach ($project->getServiceContainer() as $service) {
            $containerType = $service->getServiceContainer();

            if (!$containerType instanceof ProjectContainer) {
                continue;
            }

            if (\in_array($containerType, $addedContainerTypes, true)) {
                continue;
            }

            foreach ($this->taskProviders as $provider) {
                if ($provider->supports($containerType)) {
                    $taskfileManipulator->addTasks($provider->getTasks());
                    $addedContainerTypes[] = $containerType;
                    break;
                }
            }
        }

        $taskfileConfigPath = $this->fileSystemEnvironmentServices->getProjectTaskFilePath($project);
        $this->filesystem->dumpFile($taskfileConfigPath, $taskfileManipulator->getDataString());
    }
}
