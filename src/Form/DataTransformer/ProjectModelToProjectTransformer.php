<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Form\Model\ProjectModel;
use App\Model\Project;
use Webmozart\Assert\Assert;

class ProjectModelToProjectTransformer
{
    public function transform(Project $project): ProjectModel
    {
        return new ProjectModel()
            ->setClient($project->getClient())
            ->setProject($project->getProject())
            ->setTraefikNetwork($project->getTraefikNetwork())
            ->setEnvironmentContainer($project->getEnvironmentContainer())
            ->setOriginalProjectData($project->getClient(), $project->getProject());
    }

    public function reverseTransform(ProjectModel $projectModel, Project $project): Project
    {
        Assert::string($projectModel->getClient());
        Assert::string($projectModel->getProject());

        return $project
            ->setClient($projectModel->getClient())
            ->setProject($projectModel->getProject())
            ->setTraefikNetwork($projectModel->getTraefikNetwork())
            ->setEnvironmentContainer($projectModel->getEnvironmentContainer());
    }
}
