<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Form\Model\ServiceExternalModel;
use App\Model\Service\AbstractContainer;
use App\Services\StrategyManager\ContainerServices;
use Webmozart\Assert\Assert;

readonly class ServiceExternalModelToProjectTransformer
{
    public function __construct(
        private ContainerServices $containerServices,
    )
    {
    }

    public function transform(ServiceExternalModel $model): AbstractContainer
    {
        Assert::string($model->getServiceName());
        Assert::string($model->getVersion());
        $serviceContainer = $this->containerServices->getServiceContainer($model->getServiceName());

        if (!$serviceContainer instanceof AbstractContainer) {
            throw new \InvalidArgumentException('Service non trouvé');
        }

        $serviceContainer->setDockerVersionService($model->getVersion());
        $serviceContainer->setDockerServiceName($model->getServiceName());
        $serviceContainer->setId($model->getId());
        return $serviceContainer;
    }

    /**
     * @param AbstractContainer[] $containers
     */
    public function reverseTransform(array $containers, string $uuid): ServiceExternalModel
    {
        $model = new ServiceExternalModel();

        $container = current(array_filter(
            $containers,
            static fn(AbstractContainer $container): bool => $container->getId()->toRfc4122() === $uuid,
        ));

        if ($container) {
            $model->setId($container->getId());
            $model->setServiceName($container->getName());
            $model->setVersion($container->getVersion());
        }

        return $model;
    }
}
