<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Form\ServiceExternalType;
use App\Form\ServiceProjectType;

readonly class FormModel
{
    /**
     * @param class-string<ServiceExternalType|ServiceProjectType> $formType
     * @param array<string, mixed>                                 $option
     */
    public function __construct(
        public ServiceExternalModel|ServiceProjectModel $model,
        public string $formType,
        public array $option,
    ) {
    }
}
