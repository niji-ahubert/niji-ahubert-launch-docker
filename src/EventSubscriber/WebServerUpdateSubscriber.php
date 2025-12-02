<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ServiceExternalRemoved;
use App\Services\WebServerUpdateService;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Subscriber qui met à jour automatiquement le projet actuel
 * lors de la suppression d'un service externe webserver.
 *
 * Quand un service nginx ou apache est supprimé, ce subscriber :
 * 1. Vérifie si le projet actuel utilise ce webserver
 * 2. Le met à jour pour utiliser le webserver "local"
 * 3. Affiche un message de notification à l'utilisateur
 */
final readonly class WebServerUpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WebServerUpdateService $webServerUpdateService,
        private LoggerInterface        $logger,
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
            ServiceExternalRemoved::class => 'onServiceExternalRemoved',
        ];
    }

    /**
     * Gère la suppression d'un service externe.
     */
    public function onServiceExternalRemoved(ServiceExternalRemoved $event): void
    {
        // Ne traiter que les webservers (nginx, apache)
        if (!$event->isWebServerService()) {
            return;
        }

        $webServerName = $event->getServiceName();
        $project = $event->getProject();

        $this->logger->info('Mise à jour du projet utilisant le webserver supprimé', [
            'webserver' => $webServerName,
            'project' => sprintf('%s/%s', $project->getClient(), $project->getProject()),
        ]);

        try {
            // Mettre à jour uniquement le projet actuel
            $updateResult = $this->webServerUpdateService->updateSingleProjectWebServer(
                $webServerName,
                $project
            );


        } catch (\Exception $e) {


            $this->logger->error('Erreur lors de la mise à jour des projets', [
                'webserver' => $webServerName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

}
