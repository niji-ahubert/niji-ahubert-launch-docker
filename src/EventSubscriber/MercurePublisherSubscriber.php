<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ServerEventDispatched;
use App\Model\ServerEventModel;
use App\Services\Logging\ProjectLoggerService;
use App\Services\Mercure\MercureService;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Subscriber qui publie automatiquement les événements sur Mercure.
 *
 * Ce subscriber écoute tous les événements ServerEventDispatched
 * et les publie automatiquement via MercureService, évitant ainsi
 * les appels répétitifs à publishToMercure() dans le code métier.
 */
final readonly class MercurePublisherSubscriber implements EventSubscriberInterface
{
    private OutputFormatter $outputFormatter;

    public function __construct(
        private HubInterface         $hub,
        private ProjectLoggerService $projectLoggerService
    )
    {

    }

    /**
     * Définit les événements écoutés par ce subscriber.
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ServerEventDispatched::class => 'onServerEventDispatched',
        ];
    }


    public function onServerEventDispatched(ServerEventDispatched $event): void
    {
        $logger = $this->projectLoggerService->getLogger($event->getProject(), $event->getLoggerChannel());
        $this->logs($event->getServerEvent(), $logger);
        $this->publishToMercure(
            $event->getServerEvent(),
            $logger
        );
    }

    private function logs(ServerEventModel $message, LoggerInterface $logger): void
    {
        $level = $message->getLevel();
        $logMessage = $message->getMessage();
        $context = [
            'type' => $message->getType(),
            'pid' => $message->getPid(),
            'exit_code' => $message->getExitCode(),
            'command' => $message->getCommand(),
            'error' => $message->getError(),
        ];


        match ($level) {
            Level::Debug => $logger->debug($logMessage, $context),
            Level::Notice => $logger->notice($logMessage, $context),
            Level::Warning => $logger->warning($logMessage, $context),
            Level::Error => $logger->error($logMessage, $context),
            Level::Critical => $logger->critical($logMessage, $context),
            Level::Alert => $logger->alert($logMessage, $context),
            Level::Emergency => $logger->emergency($logMessage, $context),
            default => $logger->info($logMessage, $context)
        };
    }

    private function publishToMercure(
        ServerEventModel $message,
        LoggerInterface  $logger,
    ): void
    {


        // Création du contenu Turbo Stream
        $turboStreamContent = sprintf(
            '<turbo-stream action="append" targets="#%s">
                <template>
                    <div class="%s text-xs ">%s</div>
                </template>
            </turbo-stream>',
            htmlspecialchars(MercureService::TARGET_ID, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->getCssClassForLine($message), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(preg_replace('/\e\[[0-9;]*m/', '', $message->getMessage()), ENT_QUOTES, 'UTF-8')
        );

        $update = new Update(MercureService::MERCURE_TOPIC, $turboStreamContent);

        try {
            $this->hub->publish($update);
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la publication Mercure', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }

    /**
     * Détermine une classe CSS Tailwind selon le niveau du message.
     *
     * @param ServerEventModel $message Message à analyser
     * @return string Classe CSS Tailwind
     */
    private function getCssClassForLine(ServerEventModel $message): string
    {
        return match ($message->getLevel()) {
            Level::Info => 'text-blue-300',
            Level::Notice => 'text-cyan-300',
            Level::Warning => 'text-yellow-300',
            Level::Error, Level::Critical, Level::Emergency => 'text-red-300',
            Level::Alert => 'text-orange-300',
            default => 'text-gray-300',
        };
    }

}