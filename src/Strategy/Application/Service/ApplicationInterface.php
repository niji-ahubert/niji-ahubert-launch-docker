<?php

declare(strict_types=1);

namespace App\Strategy\Application\Service;

use App\Enum\ApplicationStep;
use App\Enum\DockerAction;
use App\Model\Service\AbstractContainer;


interface ApplicationInterface
{


    /**
     * Retourne la liste des étapes nécessaires pour cette stratégie.
     *
     * @return ApplicationStep[]
     */
    public function getSteps(): array;

    /**
     * Détermine si cette stratégie supporte le container donné.
     */
    public function supports(AbstractContainer $serviceContainer, DockerAction $dockerAction): bool;
}
