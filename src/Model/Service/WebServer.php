<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\WebServerPhp as EnumWebserver;

final class WebServer
{
    private EnumWebserver $webServer;
    private int $portWebServer = 9000;

    public function getWebServer(): EnumWebserver
    {
        return $this->webServer;
    }

    public function setWebServer(EnumWebserver $webServer): self
    {
        $this->webServer = $webServer;

        return $this;
    }

    public function getPortWebServer(): int
    {
        return $this->portWebServer;
    }

    public function setPortWebServer(int $portWebServer): self
    {
        $this->portWebServer = $portWebServer;

        return $this;
    }
}
