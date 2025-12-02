<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueClientProject extends Constraint
{
    public string $message = 'validator.project.already_exists';

    /**
     * This constraint should be applied after NotBlank validations.
     */
    /**
     * @param array<string>|null $options
     * @param array<string>|null $groups
     */
    public function __construct(?array $options = null, ?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct($options, $groups, $payload);

        if (null !== $message) {
            $this->message = $message;
        }
    }

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
