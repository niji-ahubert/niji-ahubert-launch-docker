<?php

declare(strict_types=1);

namespace App\Enum\Framework;

use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionLaravelSupported;
use App\Enum\ServiceVersion\VersionReactSupported;
use App\Enum\ServiceVersion\VersionSymfonySupported;
use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements FrameworkLanguageInterface<self>
 */
enum FrameworkLanguagePhp: string implements FrameworkLanguageInterface, TranslatableInterface
{
    use UtilityTrait;

    case SYMFONY = 'symfony';
    case LARAVEL = 'laravel';
    case PHP = 'custom';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::SYMFONY => $translator->trans('framework.language.php.symfony', locale: $locale),
            self::LARAVEL => $translator->trans('framework.language.php.laravel', locale: $locale),
            self::PHP => $translator->trans('framework.language.php.custom', locale: $locale),
        };
    }

    /**
     * @phpstan-return VersionSymfonySupported|VersionLaravelSupported|null
     */
    public function getFrameworkVersionEnum(string $value): ?VersionFrameworkSupportedInterface
    {
        return match ($this) {
            self::SYMFONY => VersionSymfonySupported::tryFrom($value),
            self::LARAVEL => VersionLaravelSupported::tryFrom($value),
            self::PHP => null,
        };
    }
}
