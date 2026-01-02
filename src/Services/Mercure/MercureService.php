<?php

declare(strict_types=1);

namespace App\Services\Mercure;

use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Event\ServerEventDispatched;
use App\Model\Project;
use App\Model\ServerEventModel;
use Monolog\Level;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final class MercureService
{
    public const string MERCURE_TOPIC = 'chat';

    public const string TARGET_ID = 'messages';
    private ?Project $project = null;
    private ?LoggerChannel $loggerChannel = null;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function initialize(Project $project, LoggerChannel $loggerChannel): void
    {
        $this->project = $project;
        $this->loggerChannel = $loggerChannel;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function getLoggerChannel(): ?LoggerChannel
    {
        return $this->loggerChannel;
    }

    /**
     * Méthode helper pour dispatcher un événement ServerEventModel.
     *
     * Cette méthode simplifie l'envoi d'événements en créant automatiquement
     * le ServerEventModel et en le dispatchant pour publication automatique via Mercure.
     *
     * Usage:
     * ```php
     * $this->mercureService->dispatch(
     *     type: TypeLog::ERROR,
     *     message: '❌ Erreur',
     *     level: Level::Error,
     * );
     * ```
     *
     * @param string      $message  Message de l'événement
     * @param TypeLog     $type     Type de l'événement
     * @param Level       $level    Niveau de log
     * @param string|null $error    Message d'erreur optionnel
     * @param int|null    $pid      ID du processus optionnel
     * @param int|null    $exitCode Code de sortie optionnel
     * @param string|null $command  Commande exécutée optionnelle
     */
    public function dispatch(
        string $message,
        TypeLog $type = TypeLog::LOG,
        Level $level = Level::Info,
        ?string $error = null,
        ?int $pid = null,
        ?int $exitCode = null,
        ?string $command = null,
    ): void {
        $event = new ServerEventModel(
            type: $type,
            message: $message,
            level: $level,
            pid: $pid,
            exitCode: $exitCode,
            command: $command,
            error: $error,
        );
        Assert::isInstanceOf($this->project, Project::class);
        Assert::isInstanceOf($this->loggerChannel, LoggerChannel::class);
        $this->eventDispatcher->dispatch(
            new ServerEventDispatched($event, $this->project, $this->loggerChannel),
        );
    }
}
