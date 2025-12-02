<?php

declare(strict_types=1);

namespace App\Enum\ServiceVersion;

use App\Enum\UtilityTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements VersionServiceSupportedInterface<self>
 */
enum VersionNodeSupported: string implements TranslatableInterface, VersionServiceSupportedInterface
{
    use UtilityTrait;

    case NODE18 = '18';
    case NODE20 = '20';
    case NODE22 = '22';
    case NODE23 = '23';
    case NODE24 = '24';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->value;
    }
}
