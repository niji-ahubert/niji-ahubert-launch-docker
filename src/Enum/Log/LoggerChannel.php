<?php

declare(strict_types=1);

namespace App\Enum\Log;

enum LoggerChannel: string
{
    case START = 'start';
    case BUILD = 'build';
    case STOP = 'stop';

    case DELETE = 'delete';
}
