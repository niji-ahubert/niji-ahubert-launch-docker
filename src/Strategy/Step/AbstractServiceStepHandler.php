<?php

declare(strict_types=1);

namespace App\Strategy\Step;

use App\Model\Project;
use App\Model\Service\AbstractContainer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::APP_STEP_HANDLER)]
abstract readonly class AbstractServiceStepHandler extends AbstractStepHandler
{
    public const APP_STEP_HANDLER = 'app.step_handler';

    /**
     * Exécute l'étape si elle est supportée.
     */
    abstract public function __invoke(AbstractContainer $serviceContainer, Project $project): void;

}
