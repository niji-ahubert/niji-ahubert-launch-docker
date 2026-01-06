<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionNestSupported;
use Webmozart\Assert\Assert;

final class FrameworkNest extends AbstractFramework
{
    public function __construct()
    {
        $this->name = FrameworkLanguageNode::NEST;
        $this->frameworkVersionSupported = VersionNestSupported::values();
        $this->useComposer = false;
        $this->extensionsRequired = [];
        parent::__construct();
    }

    /**
     * @return FrameworkLanguageNode|null
     *
     * @phpstan-return FrameworkLanguageInterface<FrameworkLanguageNode>|null
     */
    public function getFrameworkEnum(string $stringEnumValue): ?FrameworkLanguageInterface
    {
        return FrameworkLanguageNode::tryFrom($stringEnumValue);
    }

    /**
     * @return VersionNestSupported|null
     */
    public function getVersionFrameworkEnum(): ?VersionFrameworkSupportedInterface
    {
        Assert::string($this->frameworkVersion);

        return VersionNestSupported::tryFrom($this->frameworkVersion);
    }

    public function support(string $frameworkChoose): bool
    {
        return FrameworkLanguageNode::NEST === FrameworkLanguageNode::tryFrom($frameworkChoose);
    }
}
