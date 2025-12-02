<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Enum\ContainerType\ServiceContainer;
use App\Form\Model\ServiceExternalModel;
use App\Model\Service\AbstractContainer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Webmozart\Assert\Assert;

class UniqueServiceNameEnumValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueServiceNameEnum) {
            throw new UnexpectedTypeException($constraint, UniqueServiceNameEnum::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $object = $this->context->getObject();
        if (!$object instanceof ServiceExternalModel) {
            return;
        }
        Assert::string($value);
        $selectedEnum = ServiceContainer::from($value);
        $existingServices = $object->getAllServices() ?? [];

        /** @var AbstractContainer $existingService */
        foreach ($existingServices as $existingService) {
            if ($existingService->getServiceContainer() === $selectedEnum) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ container_name }}', $value)
                    ->addViolation();

                return;
            }
        }
    }
}
