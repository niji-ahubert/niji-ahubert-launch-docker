<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionLaravelSupported;
use App\Enum\ServiceVersion\VersionSymfonySupported;
use App\Form\Model\ServiceProjectModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidVersionFrameworkValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidVersionFramework) {
            throw new UnexpectedTypeException($constraint, ValidVersionFramework::class);
        }

        if (!$value instanceof ServiceProjectModel) {
            throw new UnexpectedValueException($value, ServiceProjectModel::class);
        }

        // Si pas de framework sélectionné, on ignore
        if (!$value->getFramework() instanceof FrameworkLanguageInterface) {
            return;
        }

        // Si pas de version framework, on ignore
        if (!$value->getVersionFramework() instanceof VersionFrameworkSupportedInterface) {
            return;
        }

        $framework = $value->getFramework();
        $versionFramework = $value->getVersionFramework();

        // Vérification pour les frameworks PHP
        if ($framework instanceof FrameworkLanguagePhp) {
            switch ($framework) {
                case FrameworkLanguagePhp::SYMFONY:
                    if (!$versionFramework instanceof VersionSymfonySupported) {
                        $this->context->buildViolation($constraint->message)
                            ->addViolation();
                    }
                    break;
                case FrameworkLanguagePhp::LARAVEL:
                    if (!$versionFramework instanceof VersionLaravelSupported) {
                        $this->context->buildViolation($constraint->message)
                            ->addViolation();
                    }
                    break;
            }
        }
    }
}
