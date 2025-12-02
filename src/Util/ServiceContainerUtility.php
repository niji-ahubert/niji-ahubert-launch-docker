<?php

namespace App\Util;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Environment;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Model\Service\AbstractContainer;

final readonly class ServiceContainerUtility
{

    public static function isSymfonyDevService(AbstractContainer $service, Environment $environment): bool
    {
        if (Environment::DEV !== $environment) {
            return false;
        }

        if (!$service->getServiceContainer() instanceof ProjectContainer) {
            return false;
        }

        if (ProjectContainer::PHP !== $service->getServiceContainer()) {
            return false;
        }

        $framework = $service->getFramework();
        if (null === $framework) {
            return false;
        }

        return FrameworkLanguagePhp::SYMFONY === $framework->getName();
    }


    public static function getSymfonyDebugMessage(): string
    {
        return <<<'MESSAGE'

            ℹ️  Pour activer la Debug Bar Symfony, ajoutez dans config/packages/framework.yaml:

            when@dev:
                framework:
                    trusted_proxies: '%env(TRUSTED_PROXIES)%'
                    trusted_headers:
                        - 'x-forwarded-for'
                        - 'x-forwarded-host'
                        - 'x-forwarded-proto'
                        - 'x-forwarded-port'
                        - 'x-forwarded-prefix'
            MESSAGE;
    }
}