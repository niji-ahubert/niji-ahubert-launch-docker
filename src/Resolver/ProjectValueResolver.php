<?php

namespace App\Resolver;

use App\Attribute\EnrichedProject;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final readonly class ProjectValueResolver implements ValueResolverInterface
{
    public function __construct(
        private FileSystemEnvironmentServices $environmentServices
    )
    {
    }

    /**
     * @return \Generator<Project|null>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        if ($argument->getType() !== Project::class) {
            return [];
        }

        $client = $request->query->get('client');
        $projectName = $request->query->get('project');

        if (!$client || !$projectName) {
            return [];
        }

        $project = new Project();
        $project->setClient($client);
        $project->setProject($projectName);

        $attributes = $argument->getAttributes(EnrichedProject::class);
        if (!empty($attributes)) {
            $project = $this->environmentServices->loadEnvironments($project);
        }

        yield $project;
    }
}