<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;


/**
 * @template T as VersionSymfonySupported|VersionReactSupported|VersionLaravelSupported
 *
 * @return T
 */
interface VersionFrameworkSupportedInterface
{
    public function getValue(): string;
}
