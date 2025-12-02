<?php

namespace App\Util;

use App\Model\Project;
use App\Model\Service\AbstractContainer;

final readonly class DockerComposeUtility
{

    public static function getProjectServiceName(Project $project, AbstractContainer $service): string
    {
        return \sprintf('%s-%s-%s-%s', $project->getClient(), $project->getProject(), $service->getFolderName(), $project->getEnvironmentContainer()->value);
    }


}