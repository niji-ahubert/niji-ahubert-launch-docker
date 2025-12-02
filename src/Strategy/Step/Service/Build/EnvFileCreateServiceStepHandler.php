<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Services\StrategyManager\EnvFileGeneratorService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use App\Strategy\Step\AbstractServiceStepHandler;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;

final readonly class EnvFileCreateServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices   $fileSystemEnvironmentServices,
        private Generator               $makerGenerator,
        private EnvFileGeneratorService $envFileGeneratorService,
        MercureService                  $mercureService,
        ProcessRunnerService            $processRunner,
        private Filesystem              $filesystem,
        private string                  $projectDir,
    )
    {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {

        $this->mercureService->dispatch(
            message: '📦 Création mise à jour des .env',
            type: TypeLog::START
        );

        $this->filesystem->copy(sprintf('%s/%s', $this->projectDir, FileSystemEnvironmentServices::BIN_ENTRYPOINT_ADDON_SH), $this->fileSystemEnvironmentServices->getProjectComponentEntrypointAddonPath($project, $serviceContainer));

        if ($this->filesystem->exists(sprintf('%s/.env', $this->projectDir))) {
            $this->filesystem->copy(sprintf('%s/.env', $this->projectDir), sprintf('%s/.env.niji-launcher', $this->projectDir));
        }

        $configPath = $this->fileSystemEnvironmentServices->getConfigPath($project);

        // Regenerate environment file on each run (specific to docker startup)
        $envContent = $this->envFileGeneratorService->generateEnvContent($serviceContainer, $project);
        $envFilePath = \sprintf('%s/%s.env', $configPath, $serviceContainer->getFolderName());

        $this->makerGenerator->dumpFile($envFilePath, $envContent);
        $this->makerGenerator->writeChanges();

        $this->mercureService->dispatch(
            message: '✅ Création mise à jour des .env success',
            type: TypeLog::COMPLETE,
            exitCode: 0
        );
    }

    public static function getPriority(): int
    {
        return 1;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::ENV_FILE;
    }
}
