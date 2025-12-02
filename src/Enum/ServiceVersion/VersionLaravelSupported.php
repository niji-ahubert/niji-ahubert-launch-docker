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

    case LA11 = '11.0';
    case LA10 = '10.0';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::LA11 => $translator->trans('version.laravel.11', locale: $locale),
            self::LA10 => $translator->trans('version.laravel.10', locale: $locale),
        };
    }
}
