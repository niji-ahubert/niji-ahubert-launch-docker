<?php

declare(strict_types=1);

namespace App\Form\Service;

use App\Enum\TypeService;
use App\Form\Model\FormModel;
use App\Form\Model\ServiceExternalModel;
use App\Form\Model\ServiceProjectModel;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class FormServiceService
{
    /**
     * @param iterable<ServiceExternalModelService|ServiceProjectModelService> $modelServices
     */
    public function __construct(
        private FileSystemEnvironmentServices $environmentServices,
        #[AutowireIterator('model_service_service')]
        private iterable                      $modelServices,
    )
    {
    }
    
    public function getModelOption(TypeService $typeService, Project $project, ?string $uuid = null): FormModel
    {
        return $this->getModelService($typeService)->getModel($project, $uuid);
    }

    public function saveService(TypeService $typeService, Project $loadedProject, ServiceExternalModel|ServiceProjectModel $model): void
    {
        $serviceContainer = $this->getModelService($typeService)->transform($model, $loadedProject);
        $loadedProject->removeServiceContainer($serviceContainer);
        $loadedProject->addServiceContainer($serviceContainer);

        $this->environmentServices->saveEnvironments($loadedProject);
    }

    public function removeService(TypeService $typeService, Project $loadedProject, ServiceExternalModel|ServiceProjectModel $model): void
    {
        $serviceContainer = $this->getModelService($typeService)->transform($model, $loadedProject);
        $loadedProject->removeServiceContainer($serviceContainer);

        $this->environmentServices->saveEnvironments($loadedProject);
    }

    private function getModelService(TypeService $typeService): ServiceExternalModelService|ServiceProjectModelService
    {
        foreach ($this->modelServices as $modelService) {
            if ($modelService->support($typeService)) {
                return $modelService;
            }
        }

        throw new \RuntimeException('No model service found');
    }
}
