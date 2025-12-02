<?php

declare(strict_types=1);

namespace App\Services\StrategyManager;

use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\EnvVariable\EnvVariableStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class EnvFileGeneratorService
{
    /**
     * @param iterable<EnvVariableStrategyInterface> $strategies
     */
    public function __construct(
        #[AutowireIterator(EnvVariableStrategyInterface::class)]
        private iterable $strategies,
    )
    {
    }

    /**
     * @return array<string, int|string>
     */
    public function generateSocleEnv(AbstractContainer $serviceContainer, Project $project): array
    {
        return $this->getStrategyForContainer($serviceContainer)->generateSocleEnvVariables($serviceContainer, $project);

    }

    public function generateEnvContent(AbstractContainer $serviceContainer, Project $project): string
    {
        $strategy = $this->getStrategyForContainer($serviceContainer);
        $variables = $strategy->generateVariables($serviceContainer, $project);

        $content = '';
        foreach ($variables as $key => $value) {
            $content .= \sprintf('%s=%s%s', $key, $value, \PHP_EOL);
        }

        return $content;
    }

    private function getStrategyForContainer(AbstractContainer $serviceContainer): EnvVariableStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($serviceContainer)) {
                return $strategy;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Aucune stratégie trouvée pour le type de service: %s', $serviceContainer->getServiceContainer()->value));
    }
}
