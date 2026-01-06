<?php

declare(strict_types=1);

namespace App\Form\Service;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\DataStorage;
use App\Enum\WebServerPhp;
use App\Model\Project;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AvailableServicesProvider
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return DataStorage[]
     */
    public function getAvailableDataStorages(Project $project): array
    {
        $availableStorages = [];

        foreach ($project->getServiceContainer() as $container) {
            if ($container->getServiceContainer() instanceof ProjectContainer) {
                continue;
            }

            $serviceName = strtolower($container->getName());
            $storageEnum = DataStorage::tryFrom($serviceName);

            if ($storageEnum && !\in_array($storageEnum, $availableStorages, true)) {
                $availableStorages[] = $storageEnum;
            }
        }

        return $availableStorages;
    }

    /**
     * @return WebServerPhp[]
     */
    public function getAvailableWebServers(Project $project): array
    {
        $availableWebServers = [WebServerPhp::LOCAL];

        foreach ($project->getServiceContainer() as $container) {
            $serviceName = strtolower($container->getName());
            $webServerEnum = WebServerPhp::tryFrom($serviceName);

            if ($webServerEnum && !\in_array($webServerEnum, $availableWebServers, true)) {
                $availableWebServers[] = $webServerEnum;
            }
        }

        return $availableWebServers;
    }

    /**
     * Formatte les enums en choix pour les formulaires.
     *
     * @param array<\BackedEnum&TranslatableInterface> $enums
     *
     * @return array<string, int|string>
     */
    public function formatAsChoices(array $enums): array
    {
        $choices = [];
        foreach ($enums as $enum) {
            $choices[$enum->trans($this->translator)] = $enum->value;
        }

        return $choices;
    }

    /**
     * Formatte les enums en JSON pour les API.
     *
     * @param array<\BackedEnum&TranslatableInterface> $enums
     *
     * @return array<int, array<string, int|string>>
     */
    public function formatAsJson(array $enums): array
    {
        $choices = [];
        foreach ($enums as $enum) {
            $choices[] = [
                'value' => $enum->value,
                'label' => $enum->trans($this->translator),
                'enum_class' => $enum::class,
            ];
        }

        return $choices;
    }
}
