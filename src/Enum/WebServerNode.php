<?php

declare(strict_types=1);

namespace App\Enum;

use App\Enum\ContainerType\ServiceContainer;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum WebServerNode: string implements TranslatableInterface
{
    use UtilityTrait;

    case LOCAL = 'local';


    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::LOCAL => $translator->trans('webserver.local', locale: $locale),
        };
    }
}
