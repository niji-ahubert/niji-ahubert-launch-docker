<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Start;

use App\Enum\ApplicationStep;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\Step\AbstractStartServiceStepHandler;

final readonly class ComposerInstallServiceStepHandler extends AbstractStartServiceStepHandler
{

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $this->mercureService->dispatch(
            message: 'Installation des dépendances Composer',
            type: TypeLog::START
        );

        // Clear composer cache to prevent corrupted archive issues
        $this->mercureService->dispatch(
            message: '🧹 Nettoyage du cache Composer'
        );
//
//        $clearCacheCmd = [
//            'composer',
//            'clear-cache',
//        ];
//
//        try {
//            $this->executeInContainer($project, $serviceContainer, $clearCacheCmd, '🧹 Nettoyage du cache');
//        } catch (\Exception $e) {
//            // Continue even if cache clear fails
//            $this->mercureService->dispatch(
//                message: '⚠️ Impossible de nettoyer le cache, continuation...'
//            );
//        }
//
//        $this->mercureService->dispatch(
//            message: '📦 Installation des dépendances'
//        );
//
//        $composerCmd = [
//            'composer',
//            'install',
//            '--no-scripts',
//            '--prefer-dist',
//            '--no-interaction',
//        ];
//
//        $this->executeInContainer($project, $serviceContainer, $composerCmd, '⚙️ Installation des vendor');

    }

    public static function getPriority(): int
    {
        return 3;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::COMPOSER;
    }
}
