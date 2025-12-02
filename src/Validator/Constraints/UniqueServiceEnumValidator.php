<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Form\Model\ServiceExternalModel;
use App\Model\Service\AbstractContainer;
use App\Services\StrategyManager\ContainerServices;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Webmozart\Assert\Assert;

class UniqueServiceEnumValidator extends ConstraintValidator
{
    public function __construct(private readonly ContainerServices $containerServices)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueServiceEnum) {
            throw new UnexpectedTypeException($constraint, UniqueServiceEnum::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $object = $this->context->getObject();
        if (!$object instanceof ServiceExternalModel) {
            return;
        }
        Assert::string($value);
        $container = $this->containerServices->getServiceContainer($value);
        if (!$container instanceof AbstractContainer) {
            throw new \RuntimeException('Container not found');
        }

        $existingServices = $object->getAllServices() ?? [];

        /** @var AbstractContainer $existingService */
        foreach ($existingServices as $existingService) {
            if (null !== $existingService->getDockerServiceName() && $existingService->getDockerServiceName() === $container->getDockerServiceName()) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ type }}', $existingService->getDockerServiceName())
                    ->addViolation();

                return;
            }
        }
    }
}
