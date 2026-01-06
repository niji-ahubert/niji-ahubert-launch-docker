<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionNextSupported;
use Webmozart\Assert\Assert;

final class FrameworkNext extends AbstractFramework
{
    public function __construct()
    {
        $this->name = FrameworkLanguageNode::NEXT;
        $this->frameworkVersionSupported = VersionNextSupported::values();
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
     * @return VersionNextSupported|null
     */
    public function getVersionFrameworkEnum(): ?VersionFrameworkSupportedInterface
    {
        Assert::string($this->frameworkVersion);

        return VersionNextSupported::tryFrom($this->frameworkVersion);
    }

    public function support(string $frameworkChoose): bool
    {
        return FrameworkLanguageNode::NEXT === FrameworkLanguageNode::tryFrom($frameworkChoose);
    }
}
