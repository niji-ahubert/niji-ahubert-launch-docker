<?php

namespace App\Strategy\Webserver;

use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Filesystem\Filesystem;

#[AutoconfigureTag(self::APP_STEP_HANDLER)]
abstract class abstractConfiguration
{
    public const APP_STEP_HANDLER = 'app.step_handler.webserver_configuration';

    public function __construct(
        protected FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        protected Generator                     $makerGenerator,
        protected Filesystem                    $filesystem,
    )
    {
    }

    abstract public function support(AbstractContainer $serviceContainer): bool;


    abstract public function __invoke(Project $project, AbstractContainer $serviceContainer): void;
}