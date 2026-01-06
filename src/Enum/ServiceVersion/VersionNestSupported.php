<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionFrameworkSupportedInterface<self>
 */
enum VersionNestSupported: string implements TranslatableInterface, VersionFrameworkSupportedInterface
{
    use UtilityTrait;

    case NEST11 = '11';
    case NEST10 = '10';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::NEST11 => $translator->trans('version.nest.11', locale: $locale),
            self::NEST10 => $translator->trans('version.nest.10', locale: $locale),
        };
    }
}
