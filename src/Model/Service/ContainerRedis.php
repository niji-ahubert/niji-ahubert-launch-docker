<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ServiceContainer;
use App\Enum\PhpExtension;
use App\Enum\ServiceVersion\VersionRedisSupported;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Webmozart\Assert\Assert;
use \App\Enum\ServiceVersion\VersionServiceSupportedInterface;

#[AsTaggedItem(priority: 10)]
final class ContainerRedis extends AbstractContainer implements ServiceContainerInterface
{
    public function __construct()
    {
        $this->versionSupported = VersionRedisSupported::values();
        $this->serviceContainer = ServiceContainer::REDIS;
        $this->extensionsRequired = [PhpExtension::REDIS->value];
        $this->dockerServiceName = 'redis';
        $this->dockerVersionService = VersionRedisSupported::REDIS7->value;
        parent::__construct();
    }

    /**
     * @return VersionRedisSupported|null
     */
    public function getVersionServiceEnum(): ?VersionServiceSupportedInterface
    {
        Assert::string($this->dockerVersionService);

        return VersionRedisSupported::tryFrom($this->dockerVersionService);
    }

    public function getFormType(): string
    {
        return 'service_container';
    }
}
