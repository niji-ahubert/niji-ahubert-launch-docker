<?php

declare(strict_types=1);

namespace App\Util;

use App\Enum\ContainerType\ProjectContainer;
use App\Model\DockerData;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Model\Service\ServiceContainerInterface;
use Symfony\Component\Process\Process;

final readonly class DockerUtility
{
    public static function getFinalTagName(Project $project, AbstractContainer $service): string
    {
        return \sprintf(
            '%s-%s-%s-%s',
            $project->getClient(),
            $project->getProject(),
            $service->getFolderName(),
            $project->getEnvironmentContainer()->value,
        );
    }

    /**
     * Récupère les logs d'un container Docker Compose.
     *
     * @param Project  $project               Le projet contenant les informations Docker
     * @param string   $serviceName           Le nom du service dont on veut les logs
     * @param string   $dockerComposeFilePath Le chemin vers le fichier docker-compose.yml
     * @param bool     $follow                Suivre les logs en temps réel (défaut: false)
     * @param int|null $tail                  Nombre de lignes à afficher (défaut: toutes)
     *
     * @return string Les logs du container
     */
    public static function getLogContainer(
        Project $project,
        string $serviceName,
        string $dockerComposeFilePath,
        bool $follow = false,
        ?int $tail = null,
    ): string {
        $command = [
            'docker',
            '--log-level=ERROR',
            'compose',
            '--project-name',
            self::getProjectName($project),
            '-f',
            $dockerComposeFilePath,
            'logs',
        ];

        if ($follow) {
            $command[] = '--follow';
        }

        if (null !== $tail) {
            $command[] = '--tail';
            $command[] = (string) $tail;
        }

        $command[] = $serviceName;

        $process = new Process($command);
        $process->run();

        return $process->getOutput();
    }

    /**
     * Vérifie si une image Docker existe.
     *
     * @param string $imageName Nom de l'image Docker
     *
     * @return bool True si l'image existe, false sinon
     */
    public static function dockerImageExists(string $imageName): bool
    {
        $process = new Process(['docker', 'images', '-q', $imageName]);
        $process->run();

        return '' !== trim($process->getOutput());
    }

    public static function getProjectName(Project $project): string
    {
        return \sprintf('%s-%s', $project->getClient(), $project->getProject());
    }

    public static function getDockerfileVariable(AbstractContainer $serviceContainer, Project $project): ?DockerData
    {
        $containerType = $serviceContainer->getServiceContainer();

        if (ProjectContainer::PHP === $containerType) {
            return self::getPhpDockerfileVariable($serviceContainer, $project);
        }

        if (ProjectContainer::NODE === $containerType) {
            return self::getNodeDockerfileVariable($serviceContainer, $project);
        }

        return null;
    }

    public static function getPhpDockerfileVariable(AbstractContainer $serviceContainer, Project $project, string $tagVersion = 'latest'): DockerData
    {
        $phpVersion = $serviceContainer->getDockerVersionService();
        $env = $project->getEnvironmentContainer()->value;

        // Fusion des extensions requises : service PHP, framework et services externes (MySQL, PostgreSQL, etc.)
        $extensionsRequired = $serviceContainer->getExtensionsRequired() ?? [];
        $extensionsFromFramework = $serviceContainer->getFramework()?->getExtensionsRequired() ?? [];

        $serviceContainersExtensions = array_reduce(
            $project->getServiceContainer(),
            static function (array $extensions, AbstractContainer $projectService): array {
                // Récupérer uniquement les extensions des services externes (MySQL, PostgreSQL, Redis, etc.)
                if (!$projectService instanceof ServiceContainerInterface) {
                    return $extensions;
                }

                $projectServiceExtensions = $projectService->getExtensionsRequired();
                if (null !== $projectServiceExtensions && isset($projectServiceExtensions[ProjectContainer::PHP->value])) {
                    return [...$extensions, ...$projectServiceExtensions[ProjectContainer::PHP->value]];
                }

                return $extensions;
            },
            [],
        );

        $phpExtensions = implode(
            ' ',
            array_unique(
                array_filter(array_merge($extensionsRequired, $extensionsFromFramework, $serviceContainersExtensions)),
            ),
        );

        $image_name = \sprintf('socle-php-%s-%s', $phpVersion, $env);

        return new DockerData(image_name: $image_name, tag_version: $tagVersion, from_statement: \sprintf('FROM %s:%s AS stage_dev', $image_name, $tagVersion), extensions_selected: $phpExtensions);
    }

    public static function getNodeDockerfileVariable(AbstractContainer $serviceContainer, Project $project, string $tagVersion = 'latest'): DockerData
    {
        $nodeVersion = $serviceContainer->getDockerVersionService() ?? '18';
        $env = $project->getEnvironmentContainer()->value;
        $nodeServices = array_filter(
            $project->getServiceContainer(),
            static fn (AbstractContainer $service): bool => ProjectContainer::NODE === $service->getServiceContainer(),
        );

        $index = array_search($serviceContainer, array_values($nodeServices), true);
        $image_name = \sprintf('socle-node-%s-%s', $nodeVersion, $env);

        return new DockerData(image_name: $image_name, tag_version: $tagVersion, from_statement: \sprintf('FROM %s:%s AS stage_dev', $image_name, $tagVersion), port: 3000 + $index);
    }
}
