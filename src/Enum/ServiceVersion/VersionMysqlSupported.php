<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionServiceSupportedInterface<self>
 */
enum VersionMysqlSupported: string implements TranslatableInterface, VersionServiceSupportedInterface
{
    use UtilityTrait;

    case MYSQL9 = '9';
    case MYSQL8 = '8';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::MYSQL9 => $translator->trans('version.mysql.9', locale: $locale),
            self::MYSQL8 => $translator->trans('version.mysql.8', locale: $locale),
        };
    }
}
