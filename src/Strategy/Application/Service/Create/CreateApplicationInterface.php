<?php

declare(strict_types=1);

namespace App\Strategy\Application\Service\Create;

use App\Strategy\Application\Service\ApplicationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::APP_CREATE_APPLICATION_SERVICE_STRATEGY)]
interface CreateApplicationInterface extends ApplicationInterface
{
    public const APP_CREATE_APPLICATION_SERVICE_STRATEGY = 'app.create_application_service_strategy';

}
