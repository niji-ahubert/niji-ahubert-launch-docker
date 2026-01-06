<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionFrameworkSupportedInterface<self>
 */
enum VersionNextSupported: string implements TranslatableInterface, VersionFrameworkSupportedInterface
{
    use UtilityTrait;

    case NEXT16 = '16';
    case NEXT15 = '15';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::NEXT16 => $translator->trans('version.next.16', locale: $locale),
            self::NEXT15 => $translator->trans('version.next.15', locale: $locale),
        };
    }
}
