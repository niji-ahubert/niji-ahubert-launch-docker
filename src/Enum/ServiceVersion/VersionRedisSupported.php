<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionServiceSupportedInterface<self>
 */
enum VersionRedisSupported: string implements TranslatableInterface, VersionServiceSupportedInterface
{
    use UtilityTrait;

    case REDIS7 = '7-alpine';
    case REDIS6 = '6-alpine';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::REDIS7 => $translator->trans('version.redis.7', locale: $locale),
            self::REDIS6 => $translator->trans('version.redis.6', locale: $locale),
        };
    }
}
