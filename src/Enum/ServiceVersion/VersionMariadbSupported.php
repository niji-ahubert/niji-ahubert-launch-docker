<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionServiceSupportedInterface<self>
 */
enum VersionMariadbSupported: string implements TranslatableInterface, VersionServiceSupportedInterface
{
    use UtilityTrait;

    case MARIADB11 = '11';
    case MARIADB10 = '10';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::MARIADB11 => $translator->trans('version.mariadb.11', locale: $locale),
            self::MARIADB10 => $translator->trans('version.mariadb.10', locale: $locale),
        };
    }
}
