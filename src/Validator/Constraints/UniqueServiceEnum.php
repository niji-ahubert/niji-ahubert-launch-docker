<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueServiceEnum extends Constraint
{
    public string $message = 'validator.service.type_already_exists';
}
