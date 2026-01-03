<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use Symfony\Component\Filesystem\Filesystem;

final class PhpQualityServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        protected FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        protected MercureService $mercureService,
        protected ProcessRunnerService $processRunner,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer);

        $allowVendor = ['allow-plugins.phpstan/extension-installer', 'allow-plugins.phpro/grumphp'];
        foreach ($allowVendor as $vendor) {
            $allowPluginCmd = [
                'composer',
                'config',
                '--no-plugins',
                '--no-interaction',
                $vendor,
                'true',
            ];

            $this->processRunner->run(
                $allowPluginCmd,
                '⚙️ Configuration des plugins Composer',
                $applicationProjectPath,
                ['XDEBUG_MODE' => 'off']
            );
        }

        $packagesToInstall = [
            'phpstan/phpstan',
            'friendsofphp/php-cs-fixer',
            'rector/rector',
            'phpstan/extension-installer'
        ];

        $installCmd = array_merge(
            ['composer', 'require', '--dev', '--no-install'],
            $packagesToInstall,
        );

        $this->processRunner->run(
            $installCmd,
            '📦 Ajout des composants de qualités',
            $applicationProjectPath,
            ['XDEBUG_MODE' => 'off']
        );

        $this->mercureService->dispatch(
            message: '📦 Ajout des fichiers de configuration',
        );

        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/rector/rector.php'),
            $applicationProjectPath.'/rector.php',
            true,
        );

        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/phpstan/phpstan.neon'),
            $applicationProjectPath.'/phpstan.neon',
            true,
        );
        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/phpcsfixer/.php-cs-fixer.dist.php'),
            $applicationProjectPath.'/.php-cs-fixer.dist.php',
            true,
        );
        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/editorconfig/.editorconfig'),
            $applicationProjectPath.'/.editorconfig',
            true,
        );

    }

    public static function getPriority(): int
    {
        return 2;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::PHP_QUALITY;
    }
}