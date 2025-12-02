<?php

declare(strict_types=1);

namespace App\Form\Service;

use App\Enum\TypeService;
use App\Form\DataTransformer\ServiceProjectModelToProjectTransformer;
use App\Form\Model\FormModel;
use App\Form\Model\ServiceProjectModel;
use App\Form\ServiceProjectType;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('model_service_service')]
final readonly class ServiceProjectModelService
{
    public function __construct(
        private ServiceProjectModelToProjectTransformer $serviceProjectModelTransformer,
    )
    {
    }

    public function support(TypeService $typeService): bool
    {
        return TypeService::PROJECT === $typeService;
    }

    public function getModel(Project $project, ?string $uuid = null): FormModel
    {
        $formType = ServiceProjectType::class;
        if (null === $uuid) {
            $serviceModel = new ServiceProjectModel();
            $option = ['is_edit_mode' => false, 'project' => $project];
        } else {
            $serviceModel = $this->serviceProjectModelTransformer->reverseTransform($project, $uuid);
            $option = ['is_edit_mode' => true, 'project' => $project];
        }

        return new FormModel($serviceModel, $formType, $option);
    }

    public function transform(ServiceProjectModel $model, Project $loadedProject): AbstractContainer
    {
        return $this->serviceProjectModelTransformer->transform($model);
    }

}
