<?php

declare(strict_types=1);

namespace App\Enum\ContainerType;

use App\Enum\UtilityTrait;

/**
 * @implements TypeContainerInterface<self>
 */
enum ServiceContainer: string implements TypeContainerInterface
{
    use UtilityTrait;

    case MYSQL = 'mysql';
    case PGSQL = 'postgres';
    case MARIADB = 'mariadb';
    case REDIS = 'redis';
    case APACHE = 'apache';
    case NGINX = 'nginx';
    case FRANKENPHP = 'frankenphp';
}
