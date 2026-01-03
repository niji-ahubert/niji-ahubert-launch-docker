<?php

declare(strict_types=1);

namespace App\Services\Generation;

use App\Enum\ContainerType\ServiceContainer;
use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\DockerData;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Services\StrategyManager\EnvFileGeneratorService;
use App\Util\DockerComposeUtility;
use App\Util\DockerUtility;
use App\Util\EnvVarUtility;
use Monolog\Level;
use Symfony\Component\Process\Exception\ProcessFailedException;

final readonly class BuildImageProjectService
{
    public function __construct(
        private MercureService $mercureService,
        private ProcessRunnerService $processRunnerService,
        private FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private EnvFileGeneratorService $envFileGeneratorService,
        private string $projectDir,
    ) {
    }

    /**
     * @param array<string>|null $selectedServices
     */
    public function buildProject(Project $project, ?array $selectedServices = null): void
    {
        $this->mercureService->initialize($project, LoggerChannel::BUILD);

        $this->mercureService->dispatch(
            message: '📦 Démarrage de la construction des images Docker',
            type: TypeLog::START,
        );

        $servicesToBuild = $this->getServicesToBuild($project, $selectedServices);

        if ([] === $servicesToBuild) {
            $this->mercureService->dispatch(
                message: '❌ Aucun service à construire trouvé dans le projet',
                type: TypeLog::ERROR,
                level: Level::Error,
            );

            return;
        }

        $totalServices = \count($servicesToBuild);
        $builtServices = 0;

        foreach ($servicesToBuild as $service) {
            try {
                $this->buildServiceImage($project, $service);
                $builtServices++;
            } catch (ProcessFailedException $e) {
                $this->mercureService->dispatch(
                    message: \sprintf('Échec de la construction pour le service: %s', $service->getFolderName()),
                    type: TypeLog::ERROR,
                    level: Level::Error,
                    error: $e->getMessage(),
                );
            }
        }

        $completeMessage = \sprintf('🎉 Construction terminée: %d/%d services construits avec succès', $builtServices, $totalServices);

        $this->mercureService->dispatch(
            message: $completeMessage,
            type: TypeLog::COMPLETE,
        );
    }

    /**
     * Construit l'image Docker pour un service spécifique.
     *
     * @throws ProcessFailedException Si la commande de build échoue
     */
    private function buildServiceImage(Project $project, AbstractContainer $service): void
    {
        $folderName = $service->getFolderName();
        $serviceType = $service->getServiceContainer()->value;

        $serviceStartMessage = \sprintf('Construction du service: %s (%s)', $folderName, $serviceType);
        $this->mercureService->dispatch(message: $serviceStartMessage);

        if ($service->getServiceContainer() instanceof ServiceContainer) {
            $this->mercureService->dispatch(
                message: \sprintf('✅ Sercice %s build avec succès', $service->getServiceContainer()->value),
                type: TypeLog::COMPLETE,
            );

            return;
        }

        if (!$this->fileSystemEnvironmentServices->componentEnvFileExist($project, $service)) {
            $warningMessage = \sprintf('❌ Fichier d\'environnement non trouvé: %s', $this->fileSystemEnvironmentServices->getComponentEnvFile($project, $service));
            $this->mercureService->dispatch(
                message: $warningMessage,
                type: TypeLog::ERROR,
                level: Level::Error,
            );

            return;
        }

        $objDocker = DockerUtility::getDockerfileVariable($service, $project);

        if ($objDocker instanceof DockerData && !DockerUtility::dockerImageExists($objDocker->getImageName())) {
            $command = ['docker', '--log-level=ERROR', 'compose', '-f', 'docker-compose.admin.yml', '--profile', 'builder', 'build'];
            $command[] = \sprintf('build-php-%s', $project->getEnvironmentContainer()->value);

            $envVars = $this->envFileGeneratorService->generateSocleEnv($service, $project);

            $this->processRunnerService->run(
                $command,
                'Build common image',
                $this->projectDir,
                env: $envVars,
            );
        }

        // Construction du nom du service
        $serviceName = DockerComposeUtility::getProjectServiceName($project, $service);

        $command = [
            'docker',
            '--log-level=ERROR',
            'compose',
            '--project-name', DockerUtility::getProjectName($project),
            '-f', $this->fileSystemEnvironmentServices->getDockerComposeFilePath($project),
            'build',
            $serviceName,
        ];

        $env = EnvVarUtility::loadEnvironmentVariables($this->fileSystemEnvironmentServices->getComponentEnvFile($project, $service));
        //specific override to build form socle container
        $env['WSL_PATH_FOLDER_SOCLE_ROOT'] = FileSystemEnvironmentServices::DOCKER_ROOT_DIRECTORY;
        $env['PROJECT_ROOT_FOLDER_IN_DOCKER'] = FileSystemEnvironmentServices::PROJECT_ROOT_FOLDER_IN_DOCKER;
        $env['DOCKER_BUILDKIT'] = '1';
        $env['COMPOSE_DOCKER_CLI_BUILD'] = '1';

        $this->processRunnerService->run(
            $command,
            \sprintf('Build image %s', $serviceName),
            $this->projectDir,
            env: $env,
        );
    }

    /**
     * Détermine quels services doivent être construits.
     *
     * @param Project            $project          Objet projet
     * @param array<string>|null $selectedServices Services sélectionnés ou null pour tous
     *
     * @return array<AbstractContainer> Liste des services à construire
     */
    private function getServicesToBuild(Project $project, ?array $selectedServices = null): array
    {
        $allServices = $project->getServiceContainer();

        if (null === $selectedServices) {
            return $allServices;
        }

        return array_filter($allServices, static fn (AbstractContainer $service): bool => \in_array($service->getFolderName(), $selectedServices, true));
    }
}
