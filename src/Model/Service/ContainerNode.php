<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\ServiceVersion\VersionNodeSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use App\Enum\WebServerNode;
use App\Enum\WebServerPhp;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Webmozart\Assert\Assert;

#[AsTaggedItem(priority: 1000)]
final class ContainerNode extends AbstractContainer
{
    public function __construct()
    {
        $this->versionSupported = VersionNodeSupported::values();
        $this->serviceContainer = ProjectContainer::NODE;
        $this->frameworkSupported = FrameworkLanguageNode::values();
        $this->webserverSupported = WebServerNode::values();
        $this->dockerVersionService = VersionNodeSupported::NODE24->value;
        parent::__construct();
    }

    /**
     * @return VersionNodeSupported|null
     */
    public function getVersionServiceEnum(): ?VersionServiceSupportedInterface
    {
        Assert::string($this->dockerVersionService);

        return VersionNodeSupported::tryFrom($this->dockerVersionService);
    }

    public function getFormType(): string
    {
        return 'project_container';
    }
}
