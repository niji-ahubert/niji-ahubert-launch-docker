<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DockerAction: string implements TranslatableInterface
{
    case BUILD = 'build';
    case START = 'start';
    case STOP = 'stop';
    case DELETE = 'delete';


    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::BUILD => $translator->trans('docker.action.build', locale: $locale),
            self::START => $translator->trans('docker.action.start', locale: $locale),
            self::STOP => $translator->trans('docker.action.stop', locale: $locale),
            self::DELETE => $translator->trans('docker.action.delete', locale: $locale),
        };
    }
}

