<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service pour mettre à jour les webservers des projets.
 *
 * Ce service permet de rechercher et mettre à jour tous les projets
 * qui utilisent un webserver externe spécifique (nginx, apache)
 * et les convertir en mode "local" lorsque le service externe est supprimé.
 */
final readonly class WebServerUpdateService
{
    public function __construct(
        private FileSystemEnvironmentServices $environmentServices
    )
    {
    }

    /**
     * Met à jour le projet spécifié pour utiliser le webserver local.
     *
     * @param string $webServerName Nom du webserver supprimé (nginx, apache)
     * @param Project $project Projet à mettre à jour
     * @return array{updated: int, projects: array<string>} Statistiques de mise à jour
     */
    public function updateSingleProjectWebServer(string $webServerName, Project $project): array
    {
        $updatedCount = 0;
        $updatedProjects = [];

        try {
            $hasUpdates = false;
            foreach ($project->getServiceContainer() as $container) {
                if ($this->support($container, $webServerName)) {
                    $container->getWebServer()?->setWebServer(WebServer::LOCAL);
                    $hasUpdates = true;
                }
            }

            if ($hasUpdates) {
                $this->environmentServices->saveEnvironments($project);
                $updatedCount++;
                $updatedProjects[] = sprintf('%s/%s', $project->getClient(), $project->getProject());
            }

        } catch (\Exception $e) {
            // En cas d'erreur, on retourne les statistiques vides
        }

        return [
            'updated' => $updatedCount,
            'projects' => $updatedProjects,
        ];
    }

    /**
     * Vérifie si le container doit être mis à jour.
     */
    private function support(AbstractContainer $container, string $webServerName): bool
    {
        $webServer = $container->getWebServer();

        if ($webServer === null) {
            return false;
        }

        return $webServer->getWebServer()->value === $webServerName;
    }
}
