<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Form\Model\ServiceProjectModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidFrameworkLanguageValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidFrameworkLanguage) {
            throw new UnexpectedTypeException($constraint, ValidFrameworkLanguage::class);
        }

        if (!$value instanceof ServiceProjectModel) {
            throw new UnexpectedValueException($value, ServiceProjectModel::class);
        }

        // Si pas de langage sélectionné, on ignore
        if (!$value->getLanguage() instanceof ProjectContainer) {
            return;
        }

        $language = $value->getLanguage();
        $framework = $value->getFramework();

        switch ($language) {
            case ProjectContainer::PHP:
                if (!$framework instanceof FrameworkLanguageInterface) {
                    $this->context->buildViolation('validator.service.framework_required_php')
                        ->addViolation();
                } elseif (!$framework instanceof FrameworkLanguagePhp) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ language }}', 'PHP')
                        ->addViolation();
                }
                break;
            case ProjectContainer::NODE:
                // Pour Node.js, le framework est optionnel
                if ($framework instanceof FrameworkLanguageInterface && !$framework instanceof FrameworkLanguageNode) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ language }}', 'Node.js')
                        ->addViolation();
                }
                break;
            default:
                // Pour les autres langages, le framework doit être null
                if ($framework instanceof FrameworkLanguageInterface) {
                    $this->context->buildViolation('validator.service.no_framework_allowed')
                        ->addViolation();
                }
        }
    }
}
