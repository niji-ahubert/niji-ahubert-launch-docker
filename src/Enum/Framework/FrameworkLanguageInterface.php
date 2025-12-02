<?php

declare(strict_types=1);

namespace App\Enum\Framework;


/**
 * @template T as FrameworkLanguagePhp|FrameworkLanguageNode
 *
 * @return T
 */
interface FrameworkLanguageInterface
{
    public function getValue(): string;
}
