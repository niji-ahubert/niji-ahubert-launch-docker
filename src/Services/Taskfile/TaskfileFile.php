<?php

declare(strict_types=1);

namespace App\Services\Taskfile;

use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;

final readonly class TaskfileFile
{
    public function __construct(
        private Filesystem $filesystem,
        private FileSystemEnvironmentServices $environmentServices,
        private TaskfileManipulator $taskfileManipulator,
        private Generator $makerGenerator,
    ) {
    }

    public function getTaskfile(Project $project, bool $forceCreateNewFile = false): TaskfileManipulator
    {
        $this->environmentServices->loadEnvironments($project);
        $taskfilePath = $this->environmentServices->getProjectTaskFilePath($project);

        if (false === $forceCreateNewFile && $this->filesystem->exists($taskfilePath)) {
            return $this->getContentTaskfile($taskfilePath);
        }

        if ($this->filesystem->exists($taskfilePath)) {
            $this->filesystem->remove($taskfilePath);
        }

        return $this->createTaskfile($taskfilePath);
    }

    private function createTaskfile(string $taskfilePath): TaskfileManipulator
    {
        $this->taskfileManipulator->initialize();

        $this->makerGenerator->dumpFile($taskfilePath, $this->taskfileManipulator->getDataString());
        $this->makerGenerator->writeChanges();

        return $this->taskfileManipulator;
    }

    private function getContentTaskfile(string $taskfilePath): TaskfileManipulator
    {
        $content = file_get_contents($taskfilePath);
        if (false === $content) {
            throw new \RuntimeException(\sprintf("Unable to open the file '%s'.", $taskfilePath));
        }

        $this->taskfileManipulator->initialize($content);

        return $this->taskfileManipulator;
    }
}
