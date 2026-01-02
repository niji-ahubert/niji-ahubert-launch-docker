<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\Log\TypeLog;
use Monolog\Level;

/**
 * Modèle unifié pour les événements Server-Sent Events (SSE).
 *
 * Ce modèle standardise la structure des données envoyées via SSE
 * et permet une sérialisation cohérente avec le SerializerInterface de Symfony.
 */
final readonly class ServerEventModel
{
    public function __construct(
        private TypeLog $type,
        private string $message,
        private ?Level $level = null,
        private ?int $pid = null,
        private ?int $exitCode = null,
        private ?string $command = null,
        private ?string $error = null,
    ) {
    }

    public function getType(): TypeLog
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getTimestamp(): string
    {
        return new \DateTime()->format('Y-m-d H:i:s');
    }

    public function getLevel(): ?Level
    {
        return $this->level;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
