<?php

declare(strict_types=1);

namespace App\Controller;

use App\Attribute\EnrichedProject;
use App\Enum\ContainerType\ProjectContainer;
use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\PhpExtension;
use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use App\Enum\WebServer;
use App\Form\Service\AvailableServicesProvider;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Model\Service\AbstractFramework;
use App\Services\Form\FrameworkServices;
use App\Services\StrategyManager\ContainerServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServiceProjectController extends AbstractController
{
    public function __construct(
        private readonly ContainerServices $containerServices,
        private readonly FrameworkServices $frameworkServices,
        private readonly TranslatorInterface $translator,
        private readonly AvailableServicesProvider $availableServicesProvider,
    ) {
    }

    #[Route('/api/frameworks/{language}', name: 'app_api_frameworks', methods: ['GET'])]
    public function getFrameworks(string $language): JsonResponse
    {
        $container = ProjectContainer::tryFrom($language);
        if (!$container) {
            return new JsonResponse(['error' => 'Langage invalide'], Response::HTTP_BAD_REQUEST);
        }

        $service = $this->containerServices->getServiceContainer($language);
        if (!$service instanceof AbstractContainer) {
            return new JsonResponse(['error' => 'Service non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $frameworks = $service->getFrameworkSupported() ?? [];
        $choices = [];

        foreach ($frameworks as $framework) {
            $enumValue = $container->getFrameworkEnum($framework);

            if ($enumValue instanceof FrameworkLanguageInterface) {
                $choices[] = [
                    'value' => $enumValue->getValue(),
                    'label' => $enumValue->getValue(),
                    'enum_class' => $enumValue::class,
                ];
            }
        }

        return new JsonResponse(['frameworks' => $choices]);
    }

    #[Route('/api/extensions/{language}', name: 'app_api_extensions', methods: ['GET'])]
    public function getExtensions(string $language): JsonResponse
    {
        $container = ProjectContainer::tryFrom($language);
        if (!$container) {
            return new JsonResponse(['error' => 'Langage invalide'], Response::HTTP_BAD_REQUEST);
        }

        $service = $this->containerServices->getServiceContainer($language);
        if (!$service instanceof AbstractContainer) {
            return new JsonResponse(['error' => 'Service non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $extensions = match ($language) {
            ProjectContainer::PHP->value => array_map(static fn (PhpExtension $case): PhpExtension => $case, PhpExtension::cases()),
            default => [],
        };

        $choices = [];
        foreach ($extensions as $extension) {
            $choices[] = [
                'value' => $extension->value,
                'label' => $extension->value,
                'enum_class' => $extension::class,
            ];
        }

        return new JsonResponse(['extensions' => $choices]);
    }

    #[Route('/api/versions/{language}', name: 'app_api_service_versions', methods: ['GET'])]
    public function getServiceVersions(string $language): JsonResponse
    {
        $container = ProjectContainer::tryFrom($language);
        if (!$container) {
            return new JsonResponse(['error' => 'Langage invalide'], Response::HTTP_BAD_REQUEST);
        }

        $service = $this->containerServices->getServiceContainer($language);
        if (!$service instanceof AbstractContainer) {
            return new JsonResponse(['error' => 'Service non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $versions = $service->getVersionSupported() ?? [];
        $choices = [];

        foreach ($versions as $version) {
            $enumValue = $container->getServiceVersionEnum($version);

            if ($enumValue instanceof VersionServiceSupportedInterface) {
                $choices[] = [
                    'value' => $enumValue->getValue(),
                    'label' => $enumValue->getValue(),
                    'enum_class' => $enumValue::class,
                ];
            }
        }

        return new JsonResponse(['versions' => $choices]);
    }

    #[Route('/api/versions/{language}/{framework}', name: 'app_api_framework_versions', methods: ['GET'])]
    public function getFrameworkVersions(string $language, string $framework): JsonResponse
    {
        $container = ProjectContainer::tryFrom($language);
        if (!$container) {
            return new JsonResponse(['error' => 'Langage invalide'], Response::HTTP_BAD_REQUEST);
        }

        $service = $this->containerServices->getServiceContainer($language);
        if (!$service instanceof AbstractContainer) {
            return new JsonResponse(['error' => 'Service non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $frameworkObj = $this->frameworkServices->getServiceFramework($framework);
        if (!$frameworkObj instanceof AbstractFramework) {
            return new JsonResponse(['error' => 'Framework non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $versions = $frameworkObj->getFrameworkVersionSupported() ?? [];
        $choices = [];

        foreach ($versions as $version) {
            // Récupérer l'enum du framework depuis la valeur string
            $frameworkEnum = $container->getFrameworkEnum($framework);
            $enumValue = $frameworkEnum?->getFrameworkVersionEnum($version);

            if ($enumValue instanceof VersionFrameworkSupportedInterface) {
                $choices[] = [
                    'value' => $enumValue->getValue(),  // Utiliser la valeur de l'enum (ex: '22')
                    'label' => $enumValue->getValue(),  // Afficher la valeur (ex: '22')
                    'enum_class' => $enumValue::class,
                ];
            }
        }

        return new JsonResponse(['versions' => $choices]);
    }

    #[Route('/api/webservers', name: 'app_api_webservers', methods: ['GET'])]
    public function getWebServers(#[EnrichedProject] Project $project): JsonResponse
    {
        try {
            $availableWebServers = [WebServer::LOCAL];
            foreach ($project->getServiceContainer() as $container) {
                $serviceName = strtolower($container->getName());
                $webServerEnum = WebServer::tryFrom($serviceName);

                if ($webServerEnum && !\in_array($webServerEnum, $availableWebServers, true)) {
                    $availableWebServers[] = $webServerEnum;
                }
            }

            $choices = [];
            foreach ($availableWebServers as $webServer) {
                $choices[] = [
                    'value' => $webServer->value,
                    'label' => $webServer->trans($this->translator),
                    'enum_class' => $webServer::class,
                ];
            }

            return new JsonResponse(['webservers' => $choices]);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => 'Erreur lors du chargement du projet: '.$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // src/Controller/ServiceProjectController.php
    #[Route('/api/data-storages', name: 'app_api_data_storages', methods: ['GET'])]
    public function getDataStorages(
        #[EnrichedProject]
        Project $project,
    ): JsonResponse {
        try {
            $storages = $this->availableServicesProvider->getAvailableDataStorages($project);
            $choices = $this->availableServicesProvider->formatAsJson($storages);

            return new JsonResponse(['storages' => $choices]);
        } catch (\Exception $exception) {
            return new JsonResponse(
                ['error' => 'Erreur lors du chargement des storages: '.$exception->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
