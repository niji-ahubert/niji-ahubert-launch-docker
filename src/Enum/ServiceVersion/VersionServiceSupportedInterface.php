<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

/**
 * @template T as VersionNodeSupported|VersionMariadbSupported|VersionPhpSupported|VersionMysqlSupported|VersionRedisSupported|VersionPgsqlSupported|VersionNginxSupported
 *
 * @return T
 */
interface VersionServiceSupportedInterface
{
    public function getValue(): string;
}
