<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Form\Model\ProjectModel;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Webmozart\Assert\Assert;

class UniqueClientProjectValidator extends ConstraintValidator
{
    public function __construct(
        private readonly FileSystemEnvironmentServices $environmentServices,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueClientProject) {
            throw new UnexpectedTypeException($constraint, UniqueClientProject::class);
        }

        if (!$value instanceof ProjectModel) {
            throw new UnexpectedValueException($value, ProjectModel::class);
        }

        $client = $value->getClient();
        $project = $value->getProject();

        // Skip validation if client or project is empty (NotBlank will handle this)
        if (null === $client || '' === $client || '0' === $client || (null === $project || '' === $project || '0' === $project)) {
            return;
        }

        $clientModel = $this->context->getObject();
        Assert::isInstanceOf($clientModel, ProjectModel::class);
        // Check if we're editing an existing project
        $originalProjectData = $clientModel->getOriginalProjectData();

        // Skip validation if editing the same project (when client and project names haven't changed)
        if ($originalProjectData
            && $originalProjectData['client'] === $client
            && $originalProjectData['project'] === $project) {
            return;
        }

        $projectPath = \sprintf(
            '%s/%s',
            $this->environmentServices->getPathClient($client),
            $project,
        );

        if ($this->filesystem->exists($projectPath)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ client }}', $client)
                ->setParameter('{{ project }}', $project)
                ->addViolation();
        }
    }
}
