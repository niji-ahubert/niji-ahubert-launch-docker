<?php

declare(strict_types=1);

namespace App\Enum;

enum TypeService: string
{
    case EXTERNAL = 'ServiceContainer';
    case PROJECT = 'ProjectContainer';
}
