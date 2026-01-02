<?php

declare(strict_types=1);

namespace App\Services\StrategyManager;

use App\Enum\DockerAction;
use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\Mercure\MercureService;
use App\Strategy\Application\Service\AbstractServiceStrategy;
use Monolog\Level;

/**
 * Service orchestrateur pour la création d'applications.
 *
 * Ce service utilise le pattern Strategy pour déléguer la création d'applications
 * à des stratégies spécialisées selon le type de container et de framework.
 *
 * Architecture:
 * - Strategy Pattern: Chaque type de création (Git, PHP, Symfony, Laravel, ...) est encapsulé dans une stratégie
 * - Factory Pattern: La factory sélectionne automatiquement la stratégie appropriée
 * - Ordre de vérification fixe: Les stratégies sont évaluées dans un ordre prédéfini
 */
abstract class AbstractApplicationService
{
    protected MercureService $mercureService;

    /** @var iterable<AbstractServiceStrategy> */
    protected iterable $strategies;

    /**
     * Exécute la création d'application et retourne un générateur d'événements (mode web SSE).
     *
     * @throws \ReflectionException
     */
    public function __invoke(AbstractContainer $serviceContainer, Project $project, DockerAction $dockerAction): void
    {
        $this->mercureService->initialize($project, LoggerChannel::BUILD);

        try {
            foreach ($this->strategies as $strategy) {
                if ($strategy->supports($serviceContainer, $dockerAction)) {
                    $this->mercureService->dispatch(
                        message: \sprintf('🔄 Utilisation de la stratégie: %s', new \ReflectionClass($strategy)->getShortName()),
                    );
                    $strategy->execute($serviceContainer, $project, $dockerAction);
                }
            }
        } catch (\RuntimeException $runtimeException) {
            $this->mercureService->dispatch(
                message: $runtimeException->getMessage(),
                type: TypeLog::ERROR,
                level: Level::Error,
                error: $runtimeException->getMessage(),
            );

            throw $runtimeException;
        }
    }
}
