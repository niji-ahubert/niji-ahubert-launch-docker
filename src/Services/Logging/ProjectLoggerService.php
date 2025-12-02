<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Enum\Log\LoggerChannel;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * Service de gestion des logs par projet.
 *
 * Crée et gère des loggers dédiés pour chaque projet client
 * avec sauvegarde dans projects/{client}/{project}/logs/
 */
class ProjectLoggerService
{

    /** @var array<string, LoggerInterface> */
    private array $loggers = [];

    public function __construct(
        private readonly FileSystemEnvironmentServices $fileSystemEnvironmentServices,
    )
    {
    }

    public function getLogger(Project $project, LoggerChannel $channel): LoggerInterface
    {
        $loggerKey = $this->getLoggerKey($project, $channel);

        if (isset($this->loggers[$loggerKey])) {
            return $this->loggers[$loggerKey];
        }

        $logPath = $this->fileSystemEnvironmentServices->getLogFilePath($project, $channel);
        $logDir = dirname($logPath);

        if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
            throw new \RuntimeException(sprintf('Le répertoire "%s" n\'a pas pu être créé', $logDir));
        }

        $logger = new Logger($channel->value);
        $logger->pushHandler(new StreamHandler($logPath, Level::Debug));

        $this->loggers[$loggerKey] = $logger;

        return $logger;
    }

    /**
     * Récupère le contenu des logs d'un projet.
     * @return array<int, string> Les lignes de logs
     */
    public function getLogContent(Project $project, LoggerChannel $channel, int $maxLines = 0): array
    {
        $logPath = $this->fileSystemEnvironmentServices->getLogFilePath($project, $channel);

        if (!file_exists($logPath)) {
            return [];
        }

        $content = file_get_contents($logPath);
        if ($content === false) {
            return [];
        }

        $lines = explode("\n", trim($content));

        if ($maxLines > 0 && count($lines) > $maxLines) {
            return array_slice($lines, -$maxLines);
        }

        return $lines;
    }

    /**
     * Liste tous les canaux de logs disponibles pour un projet.
     *
     * @param Project $project Le projet
     * @return array<LoggerChannel>
     */
    public function getAvailableChannels(Project $project): array
    {
        $logDir = $this->fileSystemEnvironmentServices->getLogFilePath($project);

        if (!is_dir($logDir)) {
            return [];
        }

        $channels = [];
        $files = scandir($logDir);

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            if (str_ends_with($file, FileSystemEnvironmentServices::EXT_LOG)) {
                $channel = LoggerChannel::tryFrom(str_replace(FileSystemEnvironmentServices::EXT_LOG, '', $file));
                Assert::isInstanceOf($channel, \BackedEnum::class);
                $channels[] = $channel;
            }
        }

        return $channels;
    }

    /**
     * Efface les logs d'un projet pour un canal spécifique.
     * @return int Le nombre de fichiers supprimés
     */
    public function clearLogs(Project $project, ?LoggerChannel $channel = null): int
    {
        $deleted = 0;

        if ($channel === null) {
            $channels = $this->getAvailableChannels($project);
            foreach ($channels as $enumChannel) {
                if ($this->deleteLogFile($project, $enumChannel)) {
                    $deleted++;
                    $loggerKey = $this->getLoggerKey($project, $enumChannel);
                    unset($this->loggers[$loggerKey]);
                }
            }
        } else if ($this->deleteLogFile($project, $channel)) {
            $deleted++;
            $loggerKey = $this->getLoggerKey($project, $channel);
            unset($this->loggers[$loggerKey]);
        }

        return $deleted;
    }

    private function getLoggerKey(Project $project, LoggerChannel $channel): string
    {
        return sprintf('%s_%s_%s', $project->getClient(), $project->getProject(), $channel->value);
    }


    private function deleteLogFile(Project $project, LoggerChannel $channel): bool
    {
        $logPath = $this->$this->fileSystemEnvironmentServices->getLogFilePath($project, $channel);

        if (file_exists($logPath)) {
            return unlink($logPath);
        }

        return false;
    }
}
