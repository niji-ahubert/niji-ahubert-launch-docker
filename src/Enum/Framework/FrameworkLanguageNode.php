<?php

declare(strict_types=1);

namespace App\Enum\Framework;

use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionReactSupported;
use App\Enum\UtilityTrait;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements FrameworkLanguageInterface<self>
 */
enum FrameworkLanguageNode: string implements FrameworkLanguageInterface, TranslatableInterface
{
    use UtilityTrait;

    case REACT = 'react';
    case JS = 'javascript';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::REACT => $translator->trans('framework.language.node.react', locale: $locale),
            self::JS => $translator->trans('framework.language.node.javascript', locale: $locale),
        };
    }

    /**
     * @phpstan-return VersionFrameworkSupportedInterface<VersionReactSupported>|null
     */
    public function getFrameworkVersionEnum(string $value): ?VersionFrameworkSupportedInterface
    {
        return match ($this) {
            self::REACT => VersionReactSupported::tryFrom($value),
            self::JS => null,
        };
    }
}
