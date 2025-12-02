<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguagePhp;

final class FrameworkPhp extends AbstractFramework
{
    public function __construct()
    {
        $this->name = FrameworkLanguagePhp::PHP;
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

    public function getVersionFrameworkEnum(): null
    {
        return null;
    }

    public function support(string $frameworkChoose): bool
    {
        return FrameworkLanguagePhp::PHP === FrameworkLanguagePhp::tryFrom($frameworkChoose);
    }
}
