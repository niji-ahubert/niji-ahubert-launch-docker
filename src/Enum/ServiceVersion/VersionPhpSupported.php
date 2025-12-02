<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionServiceSupportedInterface<self>
 */
enum VersionPhpSupported: string implements TranslatableInterface, VersionServiceSupportedInterface
{
    use UtilityTrait;

    case PHP83 = '8.3';
    case PHP82 = '8.2';
    case PHP81 = '8.1';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::PHP83 => $translator->trans('version.php.8.3', locale: $locale),
            self::PHP82 => $translator->trans('version.php.8.2', locale: $locale),
            self::PHP81 => $translator->trans('version.php.8.1', locale: $locale),
        };
    }
}
