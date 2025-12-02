<?php

declare(strict_types=1);

namespace App\Controller;

use App\Attribute\EnrichedProject;
use App\Enum\DockerAction;
use App\Model\Project;
use App\Services\Generation\BuildImageProjectService;
use App\Services\Generation\DeleteProjectService;
use App\Services\Generation\ProjectGenerationService;
use App\Services\Generation\StartProjectService;
use App\Services\Generation\StopProjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DockerLogsController extends AbstractController
{
    public function __construct(
        private readonly ProjectGenerationService $projectGenerationService,
        private readonly BuildImageProjectService $buildImageProjectService,
        private readonly StartProjectService      $startProjectService,
        private readonly StopProjectService       $stopProjectService,
        private readonly TranslatorInterface      $translator,
        private readonly DeleteProjectService     $deleteProjectService
    )
    {
    }

    #[Route('/docker-logs/{action}', name: 'app_docker_logs', methods: ['GET'])]
    public function index(#[EnrichedProject] Project $project, DockerAction $action): Response
    {
        return $this->render('docker_logs/logs.index.html.twig', [
            'project' => $project,
            'client' => $project->getClient(),
            'title' => $action->trans($this->translator),
            'route' => sprintf('app_docker_logs_%s', $action->value)
        ]);
    }

    /**
     * Lance la construction des images Docker avec streaming des logs.
     */
    #[Route('/docker-action/build', name: 'app_docker_logs_build', methods: ['GET'])]
    public function buildProject(#[EnrichedProject] Project $project): Response
    {
        $this->projectGenerationService->generateCompleteProject($project);

        $this->buildImageProjectService->buildProject($project);

        $this->startProjectService->startProject(project: $project, onlyProjectService: true);
        $this->projectGenerationService->executeCreateApplicationService($project, DockerAction::START, onlyProjectService: true);
        //  $this->stopProjectService->stopProject($project);

        return new Response('published!');

    }

    /**
     * Lance le démarrage d'un projet avec streaming des logs.
     */
    #[Route('/docker-action/start', name: 'app_docker_logs_start', methods: ['GET'])]
    public function startProject(#[EnrichedProject] Project $project): Response
    {
        $this->startProjectService->startProject($project);
        $this->projectGenerationService->executeCreateApplicationService($project, DockerAction::START);


        return new Response('published!');
    }

    /**
     * Lance le démarrage d'un projet avec streaming des logs.
     */
    #[Route('/docker-action/stop', name: 'app_docker_logs_stop', methods: ['GET'])]
    public function stopProject(#[EnrichedProject] Project $project): Response
    {
        $this->stopProjectService->stopProject($project);

        return new Response('published!');
    }

    /**
     * Lance le démarrage d'un projet avec streaming des logs.
     */
    #[Route('/docker-action/delete', name: 'app_docker_logs_delete', methods: ['GET'])]
    public function deleteProject(#[EnrichedProject] Project $project): Response
    {
        $this->deleteProjectService->deleteProject($project);

        return $this->redirectToRoute('app_project_list_by_client', ['client' => $project->getClient()]);
    }

}
