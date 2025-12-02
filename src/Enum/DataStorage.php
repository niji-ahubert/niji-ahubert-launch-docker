<?php

declare(strict_types=1);

namespace App\Enum;

use App\Enum\ContainerType\ServiceContainer;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DataStorage: string implements TranslatableInterface
{
    use UtilityTrait;

    case MYSQL = ServiceContainer::MYSQL->value;
    case MARIADB = ServiceContainer::MARIADB->value;
    case PGSQL = ServiceContainer::PGSQL->value;
    case REDIS = ServiceContainer::REDIS->value;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::MYSQL => $translator->trans('data_storage.mysql', locale: $locale),
            self::MARIADB => $translator->trans('data_storage.mariadb', locale: $locale),
            self::PGSQL => $translator->trans('data_storage.pgsql', locale: $locale),
            self::REDIS => $translator->trans('data_storage.redis', locale: $locale),
        };
    }
}
