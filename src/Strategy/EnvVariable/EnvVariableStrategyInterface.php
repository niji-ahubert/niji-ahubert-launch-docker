<?php

declare(strict_types=1);

namespace App\Strategy\EnvVariable;

use App\Model\Project;
use App\Model\Service\AbstractContainer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface EnvVariableStrategyInterface
{
    /**
     * @return array<string, int|string>
     */
    public function generateVariables(AbstractContainer $serviceContainer, Project $project): array;

    /**
     * @return array<string, int|string>
     */
    public function generateSocleEnvVariables(AbstractContainer $serviceContainer, Project $project): array;

    public function supports(AbstractContainer $serviceContainer): bool;
}
