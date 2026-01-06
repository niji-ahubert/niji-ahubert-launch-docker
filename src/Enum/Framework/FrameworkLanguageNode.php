<?php

declare(strict_types=1);

namespace App\Enum\Framework;

use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionNestSupported;
use App\Enum\ServiceVersion\VersionNextSupported;
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

    case NEST = 'nest';
    case NEXT = 'next';
    case REACT = 'react';
    case JS = 'javascript';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::NEST => $translator->trans('framework.language.node.nest', locale: $locale),
            self::NEXT => $translator->trans('framework.language.node.next', locale: $locale),
            self::REACT => $translator->trans('framework.language.node.react', locale: $locale),
            self::JS => $translator->trans('framework.language.node.javascript', locale: $locale),
        };
    }

    /**
     * @phpstan-return VersionFrameworkSupportedInterface<VersionNestSupported|VersionNextSupported|VersionReactSupported>|null
     */
    public function getFrameworkVersionEnum(string $value): ?VersionFrameworkSupportedInterface
    {
        return match ($this) {
            self::NEST => VersionNestSupported::tryFrom($value),
            self::NEXT => VersionNextSupported::tryFrom($value),
            self::REACT => VersionReactSupported::tryFrom($value),
            self::JS => null,
        };
    }
}
