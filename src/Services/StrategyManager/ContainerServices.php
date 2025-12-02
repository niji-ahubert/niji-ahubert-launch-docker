<?php

declare(strict_types=1);

namespace App\Services\StrategyManager;

use App\Model\Service\AbstractContainer;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ContainerServices
{
    /**
     * @param iterable<AbstractContainer> $servicesContainer
     */
    public function __construct(
        #[AutowireIterator(AbstractContainer::class)]
        private iterable $servicesContainer,
    ) {
    }

    public function getServiceContainer(string $serviceContainer): ?AbstractContainer
    {
        /** @var AbstractContainer $service */
        foreach ($this->servicesContainer as $service) {
            if ($service->support($serviceContainer)) {
                $newService = new $service();
                $newService->setDockerVersionService($service->getDockerVersionService() ?? 'latest');

                return $newService;
            }
        }

        return null;
    }
}
