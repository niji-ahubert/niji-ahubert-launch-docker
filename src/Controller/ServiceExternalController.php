<?php

declare(strict_types=1);

namespace App\Controller;

use App\Attribute\EnrichedProject;
use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\TypeService;
use App\Event\ServiceExternalRemoved;
use App\Form\Model\ServiceExternalModel;
use App\Form\Service\FormServiceService;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\StrategyManager\ContainerServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

class ServiceExternalController extends AbstractController
{
    public function __construct(
        private readonly FormServiceService $formServiceService,
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ContainerServices $containerServices,
    ) {
    }

    #[Route('/project/external-services/delete/{type}/{uuid}', name: 'app_project_external_services_action_remove', methods: ['POST'])]
    public function removeService(Request $request, #[EnrichedProject] Project $project, string $type, string $uuid): Response
    {
        $submittedToken = $request->request->get('_token');
        Assert::string($submittedToken);
        if (!$this->isCsrfTokenValid('delete-service', $submittedToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_client_list');
        }

        if (($typeService = TypeService::tryFrom($type)) === null) {
            throw new BadRequestHttpException('Parameter type only support TypeService Enum value');
        }

        try {
            $modelOption = $this->formServiceService->getModelOption($typeService, $project, $uuid);
            $this->formServiceService->removeService($typeService, $project, $modelOption->model);

            // Dispatcher l'événement de suppression pour les services externes
            if (TypeService::EXTERNAL === $typeService && $modelOption->model instanceof ServiceExternalModel) {
                Assert::notNull($modelOption->model->getServiceName());
                $event = new ServiceExternalRemoved(
                    $project,
                    $typeService,
                    $modelOption->model,
                    $modelOption->model->getServiceName(),
                );
                $this->eventDispatcher->dispatch($event);
            }

            $successMessage = $this->translator->trans('service.success.deleted', ['{{ service_name }}' => $uuid]);
            $this->addFlash('success', $successMessage);
        } catch (\RuntimeException $runtimeException) {
            $this->addFlash('error', $runtimeException->getMessage());
        }

        return $this->redirectToRoute('app_project_services_list', [
            'client' => $project->getClient(),
            'project' => $project->getProject(),
            'type' => $type,
        ]);
    }

    #[Route('/project/external-services/edit/{type}/{uuid}', name: 'app_project_external_services_action_edit')]
    #[Route('/project/external-services/add/{type}', name: 'app_project_external_services_action')]
    public function actionService(Request $request, #[EnrichedProject] Project $project, string $type, ?string $uuid = null): Response
    {
        if (($typeService = TypeService::tryFrom($type)) === null) {
            throw new BadRequestHttpException('Parameter type only support TypeService Enum value');
        }

        $modelOption = $this->formServiceService->getModelOption($typeService, $project, $uuid);

        $form = $this->createForm($modelOption->formType, $modelOption->model, $modelOption->option);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && !$request->isXmlHttpRequest()) {
            try {
                $this->formServiceService->saveService($typeService, $project, $modelOption->model);

                // Message conditionnel selon la route
                $isEdit = 'app_project_external_services_action_edit' === $request->attributes->get('_route');
                $messageKey = $isEdit ? 'service.success.updated' : 'service.success.created';
                $this->addFlash('success', $this->translator->trans($messageKey));
            } catch (\Exception $e) {
                $this->addFlash('error', "Erreur lors de l'ajout du service : ".$e->getMessage());
            }
        }

        return $this->render($modelOption->model instanceof ServiceExternalModel ? 'service_external/form.html.twig' : 'service_project/form.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'type' => $type,
            'EnumDisplay' => TypeService::EXTERNAL === $typeService ? ServiceContainer::class : ProjectContainer::class,
            'lastBreadcrumb' => null === $uuid ? 'new' : 'edit',
        ]);
    }

    #[Route('/project/external-service/versions', name: 'app_service_versions')]
    public function getVersions(
        Request $request,
    ): JsonResponse {
        $serviceName = $request->query->get('service');

        if (!$serviceName) {
            return new JsonResponse(['versions' => []]);
        }

        $serviceContainer = $this->containerServices->getServiceContainer($serviceName);
        $versions = $serviceContainer instanceof AbstractContainer ? $serviceContainer->getVersionSupported() : [];

        return new JsonResponse([
            'versions' => $versions,
        ]);
    }
}
