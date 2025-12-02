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
use App\Strategy\Step\AbstractServiceStepHandler;
use Symfony\Bundle\MakerBundle\Generator;
use Webmozart\Assert\Assert;

final readonly class StartPagePhpServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService                $mercureService,
        ProcessRunnerService          $processRunner,
        private Generator             $makerGenerator
    )
    {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {


        $this->mercureService->dispatch(
            message: '📦 Création du fichier index.php'
        );
        $pathApplicationProject = $this->fileSystemEnvironmentServices->getProjectComponentPath($project, $serviceContainer);

        $indexFolder = $pathApplicationProject . '/index.php';
        $content = $this->fileSystemEnvironmentServices->getSkeletonFile('index.php.tpl');
        Assert::string($content);
        $this->makerGenerator->dumpFile($indexFolder, $content);
        $this->makerGenerator->writeChanges();

        $this->mercureService->dispatch(
            message: '✅ Création du fichier index.php terminé avec succès',
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
        return ApplicationStep::START_PAGE_PHP;
    }
}
