<?php

declare(strict_types=1);

namespace App\Form\Service;

use App\Enum\TypeService;
use App\Form\DataTransformer\ServiceExternalModelToProjectTransformer;
use App\Form\Model\FormModel;
use App\Form\Model\ServiceExternalModel;
use App\Form\ServiceExternalType;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('model_service_service')]
final readonly class ServiceExternalModelService
{
    public function __construct(
        private ServiceExternalModelToProjectTransformer $serviceExternalModelTransformer,
    )
    {
    }

    public function support(TypeService $typeService): bool
    {
        return TypeService::EXTERNAL === $typeService;
    }

    public function getModel(Project $project, ?string $uuid = null): FormModel
    {
        $service = $project->getServiceContainer();
        $formType = ServiceExternalType::class;
        if (null === $uuid) {
            $serviceModel = new ServiceExternalModel();
            $serviceModel->setAllServices($service);
            $option = ['is_edit_mode' => false, 'project' => $project];
        } else {
            $serviceModel = $this->serviceExternalModelTransformer->reverseTransform($service, $uuid);
            $option = ['is_edit_mode' => true, 'project' => $project];
        }

        return new FormModel($serviceModel, $formType, $option);
    }

    public function transform(ServiceExternalModel $model): AbstractContainer
    {
        return $this->serviceExternalModelTransformer->transform($model);
    }

}
