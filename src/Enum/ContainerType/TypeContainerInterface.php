<?php

declare(strict_types=1);

namespace App\Enum\ContainerType;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @template T as ServiceContainer|ProjectContainer
 *
 * @return T
 */
#[AutoconfigureTag('service_tag')]
interface TypeContainerInterface
{
}
