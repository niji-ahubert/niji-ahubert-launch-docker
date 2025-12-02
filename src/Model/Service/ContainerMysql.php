<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\PhpExtension;
use App\Enum\ServiceVersion\VersionMysqlSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Webmozart\Assert\Assert;

#[AsTaggedItem(priority: 100)]
final class ContainerMysql extends AbstractContainer implements ServiceContainerInterface
{
    public function __construct()
    {
        $this->versionSupported = VersionMysqlSupported::values();
        $this->serviceContainer = ServiceContainer::MYSQL;
        $this->extensionsRequired = [ProjectContainer::PHP->value => [PhpExtension::PDO_MYSQL->value]];
        $this->dockerServiceName = 'database';
        $this->dockerVersionService = VersionMysqlSupported::MYSQL9->value;
        parent::__construct();
    }

    /**
     * @return VersionMysqlSupported|null
     */
    public function getVersionServiceEnum(): ?VersionServiceSupportedInterface
    {
        Assert::string($this->dockerVersionService);

        return VersionMysqlSupported::tryFrom($this->dockerVersionService);
    }

    public function getFormType(): string
    {
        return 'service_container';
    }
}
