<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Environment: string implements TranslatableInterface
{
    case DEV = 'dev';
    case PROD = 'prod';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::DEV => $translator->trans('environment.dev', locale: $locale),
            self::PROD => $translator->trans('environment.prod', locale: $locale),
        };
    }
}
