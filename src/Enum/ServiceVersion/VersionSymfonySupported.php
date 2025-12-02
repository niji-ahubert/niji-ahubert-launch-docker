<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionFrameworkSupportedInterface<self>
 */
enum VersionSymfonySupported: string implements TranslatableInterface, VersionFrameworkSupportedInterface
{
    use UtilityTrait;

    case SF73 = '7.3.x';
    case SF64 = '6.4';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::SF73 => $translator->trans('version.symfony.7.1', locale: $locale),
            self::SF64 => $translator->trans('version.symfony.6.4', locale: $locale),
        };
    }
}
