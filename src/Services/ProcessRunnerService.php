<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Services\Mercure\MercureService;
use Monolog\Level;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;


final readonly class ProcessRunnerService
{


    public function __construct(
        private MercureService $mercureService
    )
    {
    }

    /**
     * @param string[] $command
     * @param array<string, string|int> $env
     */
    public function run(
        array   $command,
        string  $startMessage,
        ?string $applicationProjectPath = null,
        array   $env = [],
    ): int
    {

        Assert::isInstanceOf($this->mercureService->getProject(), Project::class, 'Vous devez initialize mercureService');
        Assert::isInstanceOf($this->mercureService->getLoggerChannel(), LoggerChannel::class, 'Vous devez initialize mercureService');

        $this->mercureService->dispatch(
            message: $startMessage,
            type: TypeLog::START
        );

        $process = new Process($command, $applicationProjectPath);
        $process->setTimeout(null);
        $process->setIdleTimeout(60);

        $process->run(function ($type, $buffer) {
            $primaryChunks = preg_split("/(\r\n|\r|\n)/", (string)$buffer) ?: [];

            foreach ($primaryChunks as $chunk) {
                $chunk = trim($chunk);
                if ($chunk === '') {
                    continue;
                }
                
                $secondaryChunks = preg_split('/\s(?=time=")/', $chunk) ?: [$chunk];

                foreach ($secondaryChunks as $piece) {
                    $message = rtrim($piece, "\r\n");
                    if ($message === '') {
                        continue;
                    }

                    $this->mercureService->dispatch(
                        message: $message,
                        level: $this->determineLogLevel($type, $message)
                    );
                }
            }
        }, $env);

        $exitCode = $process->getExitCode() ?? 1;

        if ($exitCode === 0) {
            $this->mercureService->dispatch(
                message: '✅ Commande terminée avec succès',
                type: TypeLog::COMPLETE
            );
        } else {
            $this->mercureService->dispatch(
                message: sprintf('❌ Commande terminée avec erreur (code: %d)', $exitCode),
                type: TypeLog::ERROR,
                level: Level::Error
            );
        }

        return $exitCode;
    }


    /**
     * Détermine le niveau de log selon le type de processus et le contenu du message.
     *
     * @param mixed $type Type de flux Process (STDOUT/STDERR)
     * @param string $message Message à analyser
     * @return Level Niveau de log Monolog
     */
    private function determineLogLevel(mixed $type, string $message): Level
    {

        if ($type === Process::ERR || str_contains($message, 'level=error')) {
            return Level::Error;
        }

        if (str_contains($message, 'level=warning')) {
            return Level::Warning;
        }

        if (str_contains($message, 'level=debug')) {
            return Level::Debug;
        }

        return Level::Info;
    }

}