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
enum VersionReactSupported: string implements TranslatableInterface, VersionFrameworkSupportedInterface
{
    use UtilityTrait;

    case REACT19 = '19';
    case REACT18 = '18';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::REACT19 => $translator->trans('version.react.19', locale: $locale),
            self::REACT18 => $translator->trans('version.react.18', locale: $locale),
        };
    }
}
