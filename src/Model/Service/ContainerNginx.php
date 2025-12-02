<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ServiceContainer;

use App\Enum\ServiceVersion\VersionNginxSupported;
use App\Enum\ServiceVersion\VersionNodeSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Webmozart\Assert\Assert;

#[AsTaggedItem(priority: 100)]
final class ContainerNginx extends AbstractContainer implements ServiceContainerInterface
{
    public function __construct()
    {
        $this->versionSupported = VersionNginxSupported::values();
        $this->serviceContainer = ServiceContainer::NGINX;
        $this->dockerServiceName = 'nginx';
        $this->dockerVersionService = VersionNginxSupported::NGINX128->value;
        parent::__construct();
    }

    /**
     * @return VersionNginxSupported|null
     */
    public function getVersionServiceEnum(): ?VersionServiceSupportedInterface
    {
        Assert::string($this->dockerVersionService);

        return VersionNginxSupported::tryFrom($this->dockerVersionService);
    }

    public function getFormType(): string
    {
        return 'service_container';
    }
}
