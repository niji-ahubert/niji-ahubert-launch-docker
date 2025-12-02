<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Start;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Strategy\Step\AbstractStartServiceStepHandler;
use Symfony\Component\Filesystem\Filesystem;

final readonly class PhpQualityServiceStepHandler extends AbstractStartServiceStepHandler
{
    public function __construct(FileSystemEnvironmentServices $fileSystemEnvironmentServices,
                                MercureService                $mercureService,
                                ProcessRunnerService          $processRunner,
                                private Filesystem            $filesystem,
                                string                        $projectDir)
    {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner, $projectDir);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer);
        $allowVendor = ['allow-plugins.phpstan/extension-installer', 'allow-plugins.phpro/grumphp'];
        foreach ($allowVendor as $vendor) {
            $allowPluginCmd = [
                'composer',
                'config',
                '--no-interaction',
                $vendor,
                'true'
            ];

            $this->executeInContainer($project, $serviceContainer, $allowPluginCmd, '⚙️ Configuration des plugins Composer');
        }


        // Then install the packages
        $packagesToInstall = [
            'phpstan/phpstan',
            'friendsofphp/php-cs-fixer',
            'rector/rector',
            'phpstan/extension-installer',
            'phpro/grumphp'
        ];

        $installCmd = array_merge(
            ['composer', 'require', '--dev', '--no-install'],
            $packagesToInstall
        );

        $this->executeInContainer($project, $serviceContainer, $installCmd, '📦 Ajout des composants de qualités');

        $this->mercureService->dispatch(
            message: '📦 Ajout des fichiers de configuration',
        );

        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/rector/rector.php'),
            $applicationProjectPath . '/rector.php',
            true
        );

        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/phpstan/phpstan.neon'),
            $applicationProjectPath . '/phpstan.neon',
            true
        );
        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/phpcsfixer/.php-cs-fixer.dist.php'),
            $applicationProjectPath . '/.php-cs-fixer.dist.php',
            true
        );
        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/editorconfig/.editorconfig'),
            $applicationProjectPath . '/.editorconfig',
            true
        );
        $this->filesystem->copy(
            $this->fileSystemEnvironmentServices->getSkeletonFile('quality/grumphp/grumphp.yml'),
            $applicationProjectPath . '/grumphp.yml',
            true
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
