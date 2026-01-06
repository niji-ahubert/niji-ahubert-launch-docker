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
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use App\Strategy\Step\Service\Build\EnvModifier\EnvModifierInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Filesystem\Filesystem;

final class EnvFileApplicationServiceStepHandler extends AbstractBuildServiceStepHandler
{
    /**
     * @param iterable<EnvModifierInterface> $envModifiers
     */
    public function __construct(
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService $mercureService,
        ProcessRunnerService $processRunner,
        private readonly Filesystem $filesystem,
        #[AutowireIterator('app.env_modifier')]
        private readonly iterable $envModifiers,
    ) {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $this->mercureService->dispatch(
            message: '📦 Création mise à jour des variables environements applicative',
            type: TypeLog::START,
        );

        $targetEnv = \sprintf('%s/.env.niji-launcher', $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer));
        $sourceEnv = \sprintf('%s/.env', $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer));

        if ($this->filesystem->exists($sourceEnv)) {
            $this->filesystem->copy($sourceEnv, $targetEnv);
            $content = file_get_contents($targetEnv);
            if (false === $content) {
                return;
            }
        } else {
            $content = '';
        }

        /** @var EnvModifierInterface $modifier */
        foreach ($this->envModifiers as $modifier) {
            $content = $modifier->modify($content, $serviceContainer, $project);
        }
        file_put_contents($targetEnv, $content);

        $this->mercureService->dispatch(
            message: '✅ Création mise à jour des variables environements applicative success',
            type: TypeLog::COMPLETE,
            exitCode: 0,
        );
    }

    public static function getPriority(): int
    {
        return 1;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::ENV_FILE_APPLICATION;
    }
}
