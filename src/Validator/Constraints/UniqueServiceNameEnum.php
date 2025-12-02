<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueServiceNameEnum extends Constraint
{
    public string $message = 'validator.service.container_name_already_exists';
}
