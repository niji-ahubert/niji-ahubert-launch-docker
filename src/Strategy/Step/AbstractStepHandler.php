<?php

declare(strict_types=1);

namespace App\Strategy\Step;

use App\Enum\ApplicationStep;
use App\Enum\DockerAction;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;


abstract readonly class AbstractStepHandler
{
    public function __construct(protected FileSystemEnvironmentServices $fileSystemEnvironmentServices,
                                protected MercureService                $mercureService,
                                protected ProcessRunnerService          $processRunner
    )
    {
    }

    /**
     * @param ApplicationStep[] $steps
     */
    public function supports(array $steps, DockerAction $dockerAction): bool
    {
        return \in_array($this->getStepName(), $steps, true) && static::getDockerAction() === $dockerAction;
    }

    abstract public static function getPriority(): int;

    /**
     * Retourne le nom de l'étape gérée par ce handler.
     */
    abstract public function getStepName(): ApplicationStep;

    abstract public static function getDockerAction(): DockerAction;
}
