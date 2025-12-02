<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\PhpExtension;
use App\Enum\ServiceVersion\VersionMariadbSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Webmozart\Assert\Assert;

#[AsTaggedItem(priority: 100)]
final class ContainerMariadb extends AbstractContainer implements ServiceContainerInterface
{
    public function __construct()
    {
        $this->versionSupported = VersionMariadbSupported::values();
        $this->serviceContainer = ServiceContainer::MARIADB;
        $this->extensionsRequired = [ProjectContainer::PHP->value => [PhpExtension::PDO_MYSQL->value]];
        $this->dockerServiceName = 'database';
        $this->dockerVersionService = VersionMariadbSupported::MARIADB11->value;
        parent::__construct();
    }

    /**
     * @return VersionMariadbSupported|null
     */
    public function getVersionServiceEnum(): ?VersionServiceSupportedInterface
    {
        Assert::string($this->dockerVersionService);

        return VersionMariadbSupported::tryFrom($this->dockerVersionService);
    }

    public function getFormType(): string
    {
        return 'service_container';
    }
}
