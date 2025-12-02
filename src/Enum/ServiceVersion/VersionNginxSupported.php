<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionServiceSupportedInterface<self>
 */
enum VersionNginxSupported: string implements TranslatableInterface, VersionServiceSupportedInterface
{
    use UtilityTrait;

    case NGINX129 = '1.29';
    case NGINX128 = '1.28';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::NGINX129 => $translator->trans('version.nginx.129', locale: $locale),
            self::NGINX128 => $translator->trans('version.nginx.128', locale: $locale),
        };
    }
}
