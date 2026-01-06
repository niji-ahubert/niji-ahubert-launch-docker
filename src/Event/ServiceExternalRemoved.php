<?php

declare(strict_types=1);

namespace App\Event;

use App\Enum\TypeService;
use App\Enum\WebServerPhp;
use App\Form\Model\ServiceExternalModel;
use App\Model\Project;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement dispatché lors de la suppression d'un service externe.
 *
 * Cet événement permet aux subscribers de réagir à la suppression
 * d'un service externe (nginx, apache, etc.) et de mettre à jour
 * automatiquement les projets qui l'utilisent.
 */
final class ServiceExternalRemoved extends Event
{
    public function __construct(
        private readonly Project $project,
        private readonly TypeService $typeService,
        private readonly ServiceExternalModel $removedService,
        private readonly string $serviceName,
    ) {
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getTypeService(): TypeService
    {
        return $this->typeService;
    }

    public function getRemovedService(): ServiceExternalModel
    {
        return $this->removedService;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * Vérifie si le service supprimé est un webserver externe.
     * Tous les webservers sauf LOCAL sont des services externes.
     */
    public function isWebServerService(): bool
    {
        $webServer = WebServerPhp::tryFrom($this->serviceName);

        return null !== $webServer && WebServerPhp::LOCAL !== $webServer;
    }
}
