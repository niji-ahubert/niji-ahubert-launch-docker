<?php

declare(strict_types=1);

namespace App\Strategy\Application\Service\Start;

use App\Strategy\Application\Service\ApplicationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::APP_START_APPLICATION_SERVICE_STRATEGY)]
interface StartApplicationInterface extends ApplicationInterface
{
    public const APP_START_APPLICATION_SERVICE_STRATEGY = 'app.start_application_service_strategy';
    
}
