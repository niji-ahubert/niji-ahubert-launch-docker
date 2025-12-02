<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\PhpExtension;
use App\Enum\ServiceVersion\VersionPgsqlSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Webmozart\Assert\Assert;

#[AsTaggedItem(priority: 100)]
final class ContainerPgsql extends AbstractContainer implements ServiceContainerInterface
{
    public function __construct()
    {
        $this->versionSupported = VersionPgsqlSupported::values();
        $this->serviceContainer = ServiceContainer::PGSQL;
        $this->extensionsRequired = [ProjectContainer::PHP->value => [PhpExtension::PDO_PGSQL->value]];
        $this->dockerServiceName = 'database';
        $this->dockerVersionService = VersionPgsqlSupported::PGSQL17->value;
        parent::__construct();
    }


    /**
     * @return VersionPgsqlSupported|null
     */
    public function getVersionServiceEnum(): ?VersionServiceSupportedInterface
    {
        Assert::string($this->dockerVersionService);

        return VersionPgsqlSupported::tryFrom($this->dockerVersionService);
    }

    public function getFormType(): string
    {
        return 'service_container';
    }
}
