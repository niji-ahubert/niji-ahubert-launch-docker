<?php

declare(strict_types=1);

namespace App\Enum;

enum ApplicationStep: string
{
    use UtilityTrait;

    case DOCKERFILE = 'dockerfile';
    case ENV_FILE = 'env_file';
    case ENV_FILE_APPLICATION = 'env_file_application';
    case INIT_GITIGNORE = 'init_gitignore';
    case INIT_FOLDER_REPOSITORY = 'init_folder_repository';
    case GIT_CLONE = 'git_clone';
    case COMPOSER_INIT = 'composer_init';
    case NODE_INIT = 'node_init';
    case SYMFONY_CREATE = 'symfony_create';
    case LARAVEL_CREATE = 'laravel_create';
    case START_PAGE_PHP = 'start_page_php';
    case COMPOSER = 'composer';
    case NPM = 'npm';
    case ACCESS_RIGHT = 'access_right';
    case CONFIGURATION_WEBSERVER = 'configuration_webserver';
    case START_SERVICE = 'start_service';

    case ENTRYPOINT = 'entrypoint';
    case ENTRYPOINT_ADDON_COPY = 'entrypoint_addon_copy';

    case PHP_QUALITY = 'php_quality';
    case PHP_QUALITY_SYMFONY = 'php_quality_symfony';
}
