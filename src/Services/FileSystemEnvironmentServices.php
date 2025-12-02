<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\Log\LoggerChannel;
use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class FileSystemEnvironmentServices
{
    public const  CONFIG_FOLDER = 'config';
    public const  SOCLE_ENV = 'socle.json';
    public const DOCKERFILE_NAME = 'LauncherDockerfile';
    public const DOCKER_COMPOSE_FILE_NAME = 'launcher-docker-compose.yml';


    public const PROJECT_IN_GENERATOR_ROOT_DIRECTORY = '/var/www/html/projects';
    public const NGINX_CONFIG_NAME = 'nginx.conf';

    public const EXT_LOG = '.log';
    private const   LOG_FILE_PATTERN = '%s' . self::EXT_LOG;
    public const  LOGS_FOLDER = 'logs';
    public const  DOCKER_FOLDER = 'docker';
    public const SRC_RESOURCES_SKELETON = 'src/Resources/skeleton';

    public const SRC_RESOURCES_SKELETON_DOCKERFILE = self::SRC_RESOURCES_SKELETON . '/dockerfile';
    public const BIN_ENTRYPOINT_ADDON_SH = 'bin/entrypoint-addon.sh';
    private readonly Finder $finder;

    private ?string $pathProject = null;

    public function __construct(
        private readonly Filesystem          $filesystem,
        private readonly SerializerInterface $serializer,
        private readonly Generator           $makerGenerator,
        private readonly string              $projectDir,
    )
    {
        $this->finder = new Finder();
    }

    public function getApplicationDockerfilePath(Project $project, AbstractContainer $serviceContainer): string
    {
        return \sprintf('%s/%s', $this->getApplicationProjectPath($project, $serviceContainer), self::DOCKERFILE_NAME);
    }

    public function getApplicationProjectPath(Project $project, AbstractContainer $serviceContainer): string
    {
        return \sprintf('%s/%s', $this->getPathProject($project), $serviceContainer->getFolderName());
    }

    public function getDockerComposeFilePath(Project $project): string
    {
        return \sprintf('%s/%s', $this->getPathProject($project), self::DOCKER_COMPOSE_FILE_NAME);
    }

    public function getConfigPath(Project $project): string
    {
        return \sprintf('%s/%s', $this->getPathProject($project), self::CONFIG_FOLDER);
    }

    /** @return string[] */
    public function getFolder(string $directory): array
    {
        if (!$this->filesystem->exists($directory)) {
            return [];
        }

        return array_map(
            static fn($dir) => $dir->getBasename(),
            iterator_to_array($this->finder->directories()->in($directory)->depth(0)),
        );
    }

    public function loadEnvironments(Project $projectEnvironment): ?Project
    {
        $socleEnv = $this->getProjectEnvFile($projectEnvironment);

        if ($this->filesystem->exists($socleEnv)) {
            return $this->serializer->deserialize(file_get_contents($socleEnv), Project::class, 'json');
        }

        return null;
    }

    public function getProjectEnvFile(Project $projectEnvironment): string
    {
        return \sprintf('%s/%s/%s', $this->getPathProject($projectEnvironment), self::CONFIG_FOLDER, self::SOCLE_ENV);
    }

    public function saveEnvironments(Project $projectEnvironment): void
    {
        $projectEnvironmentPath = $this->getPathProject($projectEnvironment);
        $jsonContent = $this->serializer->serialize($projectEnvironment, 'json', [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);
        $this->makerGenerator->dumpFile(\sprintf('%s/%s/%s', $projectEnvironmentPath, self::CONFIG_FOLDER, self::SOCLE_ENV), $jsonContent);
        $this->makerGenerator->writeChanges();
    }

    public function getPathProject(Project $projectEnvironment): ?string
    {
        $this->initializePathProject($projectEnvironment);
        return $this->pathProject;
    }

    public function updatePathProject(Project $projectEnvironment): void
    {
        $this->pathProject = \sprintf('%s/%s', $this->getPathClient($projectEnvironment->getClient()), $projectEnvironment->getProject());
    }

    public function getPathClient(string $clientName): string
    {
        return \sprintf('%s/%s', self::PROJECT_IN_GENERATOR_ROOT_DIRECTORY, $clientName);
    }

    public function createProjectLogsFolder(Project $project): void
    {
        $this->filesystem->mkdir(sprintf('%s/%s', $this->getPathProject($project), self::LOGS_FOLDER));
    }

    public function createProjectDockerFolder(Project $project): void
    {
        $this->filesystem->mkdir($this->getProjectDockerFolder($project));
    }

    public function getProjectDockerFolder(Project $project): string
    {
        return sprintf('%s/%s', $this->getPathProject($project), self::DOCKER_FOLDER);
    }

    public function getProjectDockerFolderWebserver(Project $project, WebServer $webServer): string
    {
        return sprintf('%s/%s/%s', $this->getPathProject($project), self::DOCKER_FOLDER, $webServer->getValue());
    }

    public function createClientFolder(string $clientName): void
    {
        $this->filesystem->mkdir($this->getPathClient($clientName));
    }

    /**
     * @throws \RuntimeException Si le dossier source n'existe pas ou si le dossier destination existe déjà
     */
    public function renameClientFolder(string $oldClientName, string $newClientName): void
    {
        $oldPath = $this->getPathClient($oldClientName);
        $newPath = $this->getPathClient($newClientName);

        if (!$this->filesystem->exists($oldPath)) {
            throw new \RuntimeException(\sprintf('Le dossier client "%s" n\'existe pas.', $oldClientName));
        }

        if ($this->filesystem->exists($newPath)) {
            throw new \RuntimeException(\sprintf('Le dossier client "%s" existe déjà.', $newClientName));
        }

        $this->filesystem->rename($oldPath, $newPath);
    }

    /**
     * @throws \RuntimeException Si le dossier n'existe pas
     */
    public function deleteClientFolder(string $clientName): void
    {
        $clientPath = $this->getPathClient($clientName);

        if (!$this->filesystem->exists($clientPath)) {
            throw new \RuntimeException(\sprintf('Le dossier client "%s" n\'existe pas.', $clientName));
        }

        $this->filesystem->remove($clientPath);
    }

    public function clientFolderExists(string $clientName): bool
    {
        return $this->filesystem->exists($this->getPathClient($clientName));
    }

    /**
     * @throws \RuntimeException En cas d'erreur lors du traitement des fichiers
     */
    public function updateClientNameInSocleFiles(string $oldClientName, string $newClientName): int
    {
        try {
            $modifiedCount = 0;
            $finder = new Finder();
            $socleFiles = $finder->files()
                ->name(self::SOCLE_ENV)
                ->in(self::PROJECT_IN_GENERATOR_ROOT_DIRECTORY);

            foreach ($socleFiles as $socleFile) {
                $projectPath = \dirname($socleFile->getPath());
                $clientName = basename(\dirname($projectPath));
                $projectName = basename($projectPath);

                $tempProject = new Project();
                $tempProject->setClient($clientName);
                $tempProject->setProject($projectName);

                $loadedProject = $this->loadEnvironments($tempProject);

                if ($loadedProject instanceof Project && $loadedProject->getClient() === $oldClientName) {
                    $loadedProject->setClient($newClientName);
                    $this->saveEnvironments($loadedProject);
                    $modifiedCount++;
                }

                $this->pathProject = null;
            }

            return $modifiedCount;
        } catch (\Exception $exception) {
            throw new \RuntimeException(\sprintf('Erreur lors de la mise à jour des fichiers socle.json : %s', $exception->getMessage()), 0, $exception);
        }
    }

    /**
     * @throws \RuntimeException Si le répertoire source n'existe pas ou si le répertoire destination existe déjà
     */
    public function renameProjectFolder(string $clientName, string $oldProjectName, string $newProjectName): void
    {
        $clientPath = $this->getPathClient($clientName);
        $oldProjectPath = \sprintf('%s/%s', $clientPath, $oldProjectName);
        $newProjectPath = \sprintf('%s/%s', $clientPath, $newProjectName);

        if (!$this->filesystem->exists($oldProjectPath)) {
            throw new \RuntimeException(\sprintf('Le répertoire du projet "%s/%s" n\'existe pas.', $clientName, $oldProjectName));
        }

        if ($this->filesystem->exists($newProjectPath)) {
            throw new \RuntimeException(\sprintf('Le répertoire du projet "%s/%s" existe déjà.', $clientName, $newProjectName));
        }

        $this->filesystem->rename($oldProjectPath, $newProjectPath);
    }

    public function projectFolderExists(Project $project): bool
    {
        $projectPath = \sprintf('%s/%s', $this->getPathClient($project->getClient()), $project->getProject());

        return $this->filesystem->exists($projectPath);
    }

    /**
     * @throws \RuntimeException Si le dossier n'existe pas
     */
    public function deleteProjectFolder(Project $project): void
    {
        $projectPath = \sprintf('%s/%s', $this->getPathClient($project->getClient()), $project->getProject());

        if (!$this->filesystem->exists($projectPath)) {
            throw new \RuntimeException(\sprintf('Le dossier project "%s>%s" n\'existe pas.', $project->getClient(), $project->getProject()));
        }

        $this->filesystem->remove($projectPath);
    }

    public function getProjectComponentPath(Project $projectEnvironment, AbstractContainer $serviceContainer): string
    {
        return \sprintf('%s/%s', $this->getPathProject($projectEnvironment), $serviceContainer->getFolderName());
    }

    public function getProjectComponentComposeFilePath(Project $projectEnvironment, AbstractContainer $serviceContainer): string
    {
        return \sprintf('%s/%s', $this->getProjectComponentPath($projectEnvironment, $serviceContainer), self::DOCKER_COMPOSE_FILE_NAME);
    }

    public function getProjectComponentEntrypointAddonPath(Project $projectEnvironment, AbstractContainer $serviceContainer): string
    {
        return \sprintf('%s/%s', $this->getProjectComponentPath($projectEnvironment, $serviceContainer), self::BIN_ENTRYPOINT_ADDON_SH);
    }

    public function existProjectComponentEntrypointAddon(Project $projectEnvironment, AbstractContainer $serviceContainer): bool
    {
        return $this->filesystem->exists($this->getProjectComponentEntrypointAddonPath($projectEnvironment, $serviceContainer));
    }

    public function getLogFilePath(Project $projectEnvironment, ?LoggerChannel $channel = null): string
    {
        if (null === $channel) {
            return \sprintf('%s/%s', $this->getPathProject($projectEnvironment), self::LOGS_FOLDER);
        }

        return \sprintf('%s/%s/%s', $this->getPathProject($projectEnvironment), self::LOGS_FOLDER, sprintf(self::LOG_FILE_PATTERN, $channel->value));
    }

    private function initializePathProject(Project $projectEnvironment): void
    {
        $this->pathProject = \sprintf('%s/%s', $this->getPathClient($projectEnvironment->getClient()), $projectEnvironment->getProject());
    }

    public function isDirectoryEmpty(string $directoryPath): bool
    {
        if (!is_dir($directoryPath)) {
            return true;
        }

        $iterator = new \FilesystemIterator($directoryPath);

        return !$iterator->valid();
    }

    public function componentEnvFileExist(Project $projectEnvironment, AbstractContainer $serviceContainer): bool
    {
        $envFile = \sprintf('%s/%s/%s.env', $this->getPathProject($projectEnvironment), self::CONFIG_FOLDER, $serviceContainer->getFolderName());
        return $this->filesystem->exists($envFile);
    }

    public function composerAlreadyDefined(Project $projectEnvironment, AbstractContainer $serviceContainer): bool
    {
        return $this->filesystem->exists(sprintf('%s/composer.json', $this->getProjectComponentPath($projectEnvironment, $serviceContainer)));
    }

    public function getApplicationNginxConfigPath(Project $project, AbstractContainer $serviceContainer): string
    {
        return \sprintf('%s/%s-%s', $this->getProjectDockerFolderWebserver($project, WebServer::NGINX), $serviceContainer->getFolderName(), self::NGINX_CONFIG_NAME);
    }

    public function getNginxSkeletonFile(): string
    {
        return \sprintf('%s/%s/%s', $this->projectDir, self::SRC_RESOURCES_SKELETON, self::NGINX_CONFIG_NAME);
    }

    public function getComponentEnvFile(Project $projectEnvironment, AbstractContainer $serviceContainer): string
    {
        return \sprintf('%s/%s/%s.env', $this->getPathProject($projectEnvironment), self::CONFIG_FOLDER, $serviceContainer->getFolderName());
    }

    public function getDockerSkeletonFile(AbstractContainer $serviceContainer): string
    {
        return \sprintf('%s/%s/%s.Dockerfile.tpl', $this->projectDir, self::SRC_RESOURCES_SKELETON_DOCKERFILE, $serviceContainer->getServiceContainer()->value);
    }

    public function getSkeletonFile(string $filename): string
    {
        return \sprintf('%s/%s/%s', $this->projectDir, self::SRC_RESOURCES_SKELETON, $filename);
    }
}
