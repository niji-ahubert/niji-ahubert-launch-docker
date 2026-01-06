<?php

declare(strict_types=1);

namespace App\Services\Form;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\WebServerPhp as EnumWebserver;
use App\Form\Model\ServiceProjectModel;
use App\Model\Service\WebServer;
use Webmozart\Assert\Assert;

final readonly class WebServerServices
{
    public function getWebserver(ServiceProjectModel $model): WebServer
    {
        $objWebserver = new WebServer();
        Assert::isInstanceOf($model->getWebServer(), EnumWebserver::class);
        $objWebserver->setWebServer($model->getWebServer());
        $objWebserver->setPortWebServer($this->getWebServerFreePort($model));

        return $objWebserver;
    }

    private function getWebServerFreePort(ServiceProjectModel $model): int
    {
        return match (true) {
            ProjectContainer::PHP === $model->getLanguage() => 9000,
            ProjectContainer::NODE === $model->getLanguage() => 3000,
            default => throw new \RuntimeException('Unsupported language for web server port.'),
        };
    }
}
