<?php

declare(strict_types=1);

namespace App\Services\Form;

use App\Model\Service\AbstractFramework;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class FrameworkServices
{
    /**
     * @param iterable<AbstractFramework> $serviceFrameworks
     */
    public function __construct(
        #[AutowireIterator(AbstractFramework::class)]
        private iterable $serviceFrameworks,
    ) {
    }

    public function getServiceFramework(string $frameworkChoose): ?AbstractFramework
    {
        /** @var AbstractFramework $framework */
        foreach ($this->serviceFrameworks as $framework) {
            if ($framework->support($frameworkChoose)) {
                return new $framework();
            }
        }

        return null;
    }
}
