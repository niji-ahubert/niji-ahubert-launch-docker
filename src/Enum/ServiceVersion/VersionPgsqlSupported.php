<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionServiceSupportedInterface<self>
 */
enum VersionPgsqlSupported: string implements TranslatableInterface, VersionServiceSupportedInterface
{
    use UtilityTrait;

    case PGSQL17 = '17-alpine';
    case PGSQL16 = '16-alpine';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::PGSQL17 => $translator->trans('version.pgsql.17', locale: $locale),
            self::PGSQL16 => $translator->trans('version.pgsql.16', locale: $locale),
        };
    }
}
