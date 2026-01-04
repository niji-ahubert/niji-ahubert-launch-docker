<?php

declare(strict_types=1);

namespace App\Services\Taskfile;

use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Yaml\Dumper;

class TaskfileManipulator
{
    public const TASKFILE_VERSION = '3';
    private YamlSourceManipulator $manipulator;

    public function initialize(?string $contents = null): void
    {
        if (null === $contents) {
            $this->manipulator = new YamlSourceManipulator(
                new Dumper()->dump($this->getBasicStructure(), 2),
            );
        } else {
            $this->manipulator = new YamlSourceManipulator($contents);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getTaskfileData(): array
    {
        return $this->manipulator->getData();
    }

    public function getDataString(): string
    {
        return $this->manipulator->getContents();
    }

    public function taskExists(string $name): bool
    {
        $data = $this->manipulator->getData();

        if (\array_key_exists('tasks', $data)) {
            return \array_key_exists($name, $data['tasks']);
        }

        return false;
    }

    /**
     * @param array<string, mixed> $details
     */
    public function addTask(string $name, array $details): void
    {
        $data = $this->manipulator->getData();

        if (!isset($data['tasks'])) {
            $data['tasks'] = [];
        }

        $data['tasks'][$name] = $details;

        $this->manipulator->setData($data);
    }

    /**
     * @param array<string, array<string, mixed>> $tasks
     */
    public function addTasks(array $tasks): void
    {
        foreach ($tasks as $taskName => $taskDetails) {
            if (!$this->taskExists($taskName)) {
                $this->addTask($taskName, $taskDetails);
            }
        }
    }

    public function removeTask(string $name): void
    {
        $data = $this->manipulator->getData();

        if (isset($data['tasks'][$name])) {
            unset($data['tasks'][$name]);
            $this->manipulator->setData($data);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getBasicStructure(string $version = self::TASKFILE_VERSION): array
    {
        return [
            'version' => $version,
            'dotenv' => ['.env'],
            'env' => [],
            'vars' => [],
            'tasks' => [],
        ];
    }
}
