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
use App\Strategy\Application\Service\Create\CreateApplicationInterface;
use App\Strategy\Application\Service\Start\StartApplicationInterface;
use Monolog\Level;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;


final class StartApplicationService extends AbstractApplicationService
{

    /**
     * @param iterable<AbstractServiceStrategy> $strategies
     */
    public function __construct(
        #[AutowireIterator(tag: StartApplicationInterface::APP_START_APPLICATION_SERVICE_STRATEGY)]
        iterable       $strategies,
        MercureService $mercureService,
    )
    {
        $this->strategies = $strategies;
        $this->mercureService = $mercureService;
    }
}
