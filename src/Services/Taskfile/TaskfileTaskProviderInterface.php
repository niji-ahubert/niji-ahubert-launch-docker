<?php

declare(strict_types=1);

namespace App\Services\Taskfile;

use App\Enum\ContainerType\ProjectContainer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface TaskfileTaskProviderInterface
{
    public function supports(ProjectContainer $containerType): bool;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getTasks(): array;
}
