<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\TypeService;
use App\Form\Model\ProjectModel;
use App\Form\ProjectType;
use App\Form\Service\FormProjectService;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

class ProjectController extends AbstractController
{
    public function __construct(
        private readonly FormProjectService            $formProjectService,
        private readonly FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private readonly TranslatorInterface           $translator,
    )
    {
    }

    #[Route('/project/new/{client}', name: 'app_project_new')]
    public function new(Request $request, ?string $client = null): Response
    {
        $projectModel = new ProjectModel();
        $projectModel->setClient($client);

        $form = $this->createForm(ProjectType::class, $projectModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->formProjectService->saveProject($projectModel)) {
                $this->addFlash('success', $this->translator->trans('project.flash.created_successfully'));

                return $this->redirectToRoute('app_project_edit', ['client' => $projectModel->getClient(), 'project' => $projectModel->getProject()]);
            }

            $this->addFlash('error', $this->translator->trans('flash.error.generic_update'));
            if (null === $client) {
                return $this->redirectToRoute('app_client_list');
            }

            return $this->redirectToRoute('app_project_list_by_client', ['client' => $client]);
        }

        return $this->render('project/form_new.html.twig', [
            'form' => $form->createView(),
            'project' => null,
            'folder' => 'project',
            'routePath' => null === $client ? $this->generateUrl('app_client_list') : $this->generateUrl('app_project_list_by_client', ['client' => $client]),
        ]);
    }

    #[Route('/project/edit', name: 'app_project_edit')]
    public function edit(#[MapQueryString] Project $project, Request $request): Response
    {
        if (($projectModel = $this->formProjectService->loadedProject($project)) === false) {
            $this->addFlash('error', $this->translator->trans('project.flash.not_found'));

            return $this->redirectToRoute('app_project_list_by_client', ['client' => $project->getClient()]);
        }

        $form = $this->createForm(ProjectType::class, $projectModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->formProjectService->saveProject($projectModel, $project)) {
                $this->addFlash('success', $this->translator->trans('project.flash.updated_successfully'));

                return $this->redirectToRoute('app_project_edit', ['client' => $projectModel->getClient(), 'project' => $projectModel->getProject()]);
            }

            $this->addFlash('error', $this->translator->trans('flash.error.generic_update'));
        }

        return $this->render('project/form_edit.html.twig', [
            'form' => $form->createView(),
            'project' => $projectModel,
            'folder' => 'project',

            'routePath' => $this->generateUrl('app_project_list_by_client', ['client' => $projectModel->getClient()]),
        ]);
    }

    /**
     * Supprime un client et son dossier de manière récursive.
     */
    #[Route('/project/delete', name: 'app_project_delete', methods: ['POST'])]
    public function delete(#[MapQueryString] Project $project, Request $request): Response
    {
        $token = $request->request->get('_token');
        Assert::string($token);

        if (!$this->isCsrfTokenValid('delete_project_' . $project->getProject(), $token)) {
            $this->addFlash('error', $this->translator->trans('security.csrf.invalid'));

            return $this->redirectToRoute('app_project_list_by_client', ['client' => $project->getClient()]);
        }

        if ($this->formProjectService->loadedProject($project) === false) {
            $this->addFlash('error', $this->translator->trans('project.flash.not_found'));

            return $this->redirectToRoute('app_project_list_by_client', ['client' => $project->getClient()]);
        }

        try {
            $this->fileSystemEnvironmentServices->deleteProjectFolder($project);
            $this->addFlash('success', $this->translator->trans('project.delete.success', ['%client%' => $project->getClient(), '%project%' => $project->getProject()]));
        } catch (\RuntimeException $runtimeException) {
            $this->addFlash('error', $this->translator->trans('project.delete.error', ['%error%' => $runtimeException->getMessage()]));
        }

        return $this->redirectToRoute('app_project_list_by_client', ['client' => $project->getClient()]);
    }

    #[Route('/project/services/list/{type}', name: 'app_project_services_list')]
    public function services(#[MapQueryString] Project $project, string $type): Response
    {
        if (($typeService = TypeService::tryFrom($type)) === null) {
            throw new BadRequestHttpException('Parameter type only support TypeService Enum value');
        }

        if (!($loadedProject = $this->fileSystemEnvironmentServices->loadEnvironments($project)) instanceof Project) {
            $this->addFlash('error', $this->translator->trans('project.flash.not_found'));

            return $this->redirectToRoute('app_client_list');
        }

        return $this->render('project/list-services.html.twig', [
            'project' => $loadedProject,
            'client' => $project->getClient(),
            'EnumDisplay' => TypeService::EXTERNAL === $typeService ? ServiceContainer::class : ProjectContainer::class,
        ]);
    }

    #[Route('/projects/list', name: 'app_project_list_by_client')]
    public function listByClient(#[MapQueryString] Project $project): Response
    {
        $projects = $this->formProjectService->getProjects($project);

        return $this->render('project/list.html.twig', [
            'projects' => $projects,
            'client' => $project->getClient(),
        ]);
    }
}
