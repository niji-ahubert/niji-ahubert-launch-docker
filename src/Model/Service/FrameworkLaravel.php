<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\PhpExtension;
use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionLaravelSupported;
use Webmozart\Assert\Assert;


final class FrameworkLaravel extends AbstractFramework
{
    public function __construct()
    {
        $this->name = FrameworkLanguagePhp::LARAVEL;
        $this->frameworkVersionSupported = VersionLaravelSupported::values();
        $this->useComposer = true;
        $this->extensionsRequired = [PhpExtension::ZIP->value, PhpExtension::OPCACHE->value, PhpExtension::INTL->value];
        parent::__construct();
    }

    /**
     * @return FrameworkLanguagePhp|null
     * @phpstan-return FrameworkLanguageInterface<FrameworkLanguagePhp>|null
     */
    public function getFrameworkEnum(string $stringEnumValue): ?FrameworkLanguageInterface
    {
        return FrameworkLanguagePhp::tryFrom($stringEnumValue);
    }

    /**
     * @return VersionLaravelSupported|null
     */
    public function getVersionFrameworkEnum(): ?VersionFrameworkSupportedInterface
    {
        Assert::string($this->frameworkVersion);

        return VersionLaravelSupported::tryFrom($this->frameworkVersion);
    }

    public function support(string $frameworkChoose): bool
    {
        return FrameworkLanguagePhp::LARAVEL === FrameworkLanguagePhp::tryFrom($frameworkChoose);
    }
}
