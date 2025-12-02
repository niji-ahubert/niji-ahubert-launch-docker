<?php

declare(strict_types=1);

namespace App\Enum;

use App\Enum\ContainerType\ServiceContainer;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum WebServer: string implements TranslatableInterface
{
    use UtilityTrait;

    case LOCAL = 'local';
    case APACHE = ServiceContainer::APACHE->value;
    case NGINX = ServiceContainer::NGINX->value;
    case FRANKENPHP = ServiceContainer::FRANKENPHP->value;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::LOCAL => $translator->trans('webserver.local', locale: $locale),
            self::APACHE => $translator->trans('webserver.apache', locale: $locale),
            self::NGINX => $translator->trans('webserver.nginx', locale: $locale),
            self::FRANKENPHP => $translator->trans('webserver.frankenphp', locale: $locale),
        };
    }
}
