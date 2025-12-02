<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ServiceVersion\VersionNodeSupported;
use App\Enum\ServiceVersion\VersionPhpSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use App\Form\Model\ServiceProjectModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidVersionServiceValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidVersionService) {
            throw new UnexpectedTypeException($constraint, ValidVersionService::class);
        }

        if (!$value instanceof ServiceProjectModel) {
            throw new UnexpectedValueException($value, ServiceProjectModel::class);
        }

        // Si pas de langage sélectionné, on ignore
        if (!$value->getLanguage() instanceof ProjectContainer) {
            return;
        }

        // Si pas de version service, on ignore
        if (!$value->getVersionService() instanceof VersionServiceSupportedInterface) {
            return;
        }

        $language = $value->getLanguage();
        $versionService = $value->getVersionService();

        switch ($language) {
            case ProjectContainer::PHP:
                if (!$versionService instanceof VersionPhpSupported) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ language }}', 'PHP')
                        ->addViolation();
                }
                break;
            case ProjectContainer::NODE:
                if (!$versionService instanceof VersionNodeSupported) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ language }}', 'Node.js')
                        ->addViolation();
                }
                break;
        }
    }
}
