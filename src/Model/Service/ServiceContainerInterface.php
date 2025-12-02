<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.container_service')]
interface ServiceContainerInterface
{
    /**
     * Returns the form type for this container.
     */
    public function getFormType(): string;

    public function getName(): string;

    public function getVersion(): ?string;

    public function getServiceContainer(): ServiceContainer|ProjectContainer;

    /**
     * @return string[]|null
     */
    public function getFrameworkSupported(): ?array;

    /**
     * @return string[]|null
     */
    public function getVersionSupported(): ?array;
}
