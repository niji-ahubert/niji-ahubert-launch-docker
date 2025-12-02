<?php

declare(strict_types=1);

namespace App\Enum;

trait UtilityTrait
{
    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn ($case) => $case->value, self::cases());
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
