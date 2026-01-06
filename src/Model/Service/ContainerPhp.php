<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\PhpExtension;
use App\Enum\ServiceVersion\VersionPhpSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use App\Enum\WebServerPhp;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Webmozart\Assert\Assert;

#[AsTaggedItem(priority: 1000)]
final class ContainerPhp extends AbstractContainer
{
    public function __construct()
    {
        $this->versionSupported = VersionPhpSupported::values();
        $this->serviceContainer = ProjectContainer::PHP;
        $this->frameworkSupported = FrameworkLanguagePhp::values();
        $this->extensionSupported = PhpExtension::values();
        $this->webserverSupported = WebServerPhp::values();
        $this->dockerVersionService = VersionPhpSupported::PHP83->value;
        parent::__construct();
    }

    /**
     * @return VersionPhpSupported|null
     */
    public function getVersionServiceEnum(): ?VersionServiceSupportedInterface
    {
        Assert::string($this->dockerVersionService);

        return VersionPhpSupported::tryFrom($this->dockerVersionService);
    }

    public function getFormType(): string
    {
        return 'project_container';
    }
}
