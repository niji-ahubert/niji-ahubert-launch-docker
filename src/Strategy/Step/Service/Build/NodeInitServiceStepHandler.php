<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use App\Strategy\Step\AbstractServiceStepHandler;
use Monolog\Level;

final readonly class NodeInitServiceStepHandler extends AbstractBuildServiceStepHandler
{
    /**
     * @throws \JsonException
     */
    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $this->mercureService->dispatch(
            message: ' Création du projet Node.js',
            type: TypeLog::START
        );


        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer);
        if ($this->fileSystemEnvironmentServices->isDirectoryEmpty($applicationProjectPath) === false) {
            $this->mercureService->dispatch(
                message: sprintf('Le dossier %s n\'est pas vide, opération annulée', $applicationProjectPath),
                level: Level::Warning
            );
            return;
        }

        // Création du package.json
        $packageJson = [
            'name' => strtolower(basename($applicationProjectPath)),
            'version' => '1.0.0',
            'description' => '',
            'main' => 'index.js',
            'scripts' => [
                'start' => 'node index.js',
                'dev' => 'node index.js',
            ],
            'keywords' => [],
            'author' => '',
            'license' => 'ISC',
        ];
        //TODO pass by write file makeerBundle
        $packageJsonPath = $applicationProjectPath . \DIRECTORY_SEPARATOR . 'package.json';
        file_put_contents($packageJsonPath, json_encode($packageJson, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));

        $indexJsPath = $applicationProjectPath . \DIRECTORY_SEPARATOR . 'index.js';
        file_put_contents($indexJsPath, "console.log('Hello from Node.js project!');\n");

        $this->mercureService->dispatch(
            message: 'Fichiers créés: package.json, index.js'
        );

        $this->mercureService->dispatch(
            message: ' Projet Node.js créé avec succès',
            type: TypeLog::COMPLETE,
            exitCode: 0
        );
    }

    public static function getPriority(): int
    {
        return 4;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::NODE_INIT;
    }
}
