<?php

declare(strict_types=1);

namespace App\Event;

use App\Enum\Log\LoggerChannel;
use App\Model\Project;
use App\Model\ServerEventModel;
use Symfony\Contracts\EventDispatcher\Event;


final class ServerEventDispatched extends Event
{
    public function __construct(
        private readonly ServerEventModel $serverEvent,
        private readonly Project          $project,
        private readonly LoggerChannel    $loggerChannel,
    )
    {
    }

    public function getServerEvent(): ServerEventModel
    {
        return $this->serverEvent;
    }


    public function getProject(): Project
    {
        return $this->project;
    }

    public function getLoggerChannel(): LoggerChannel
    {
        return $this->loggerChannel;
    }


}