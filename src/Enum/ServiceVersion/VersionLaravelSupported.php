<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionFrameworkSupportedInterface<self>
 */
enum VersionLaravelSupported: string implements TranslatableInterface, VersionFrameworkSupportedInterface
{
    use UtilityTrait;

    case LA13 = '13.0';
    case LA12 = '12.0';
    case LA11 = '11.0';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::LA13 => $translator->trans('version.laravel.13', locale: $locale),
            self::LA12 => $translator->trans('version.laravel.12', locale: $locale),
            self::LA11 => $translator->trans('version.laravel.11', locale: $locale),
        };
    }
}
