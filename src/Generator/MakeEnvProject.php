<?php

declare(strict_types=1);

namespace App\Generator;

use App\Enum\ContainerType\TypeContainerInterface;
use App\Model\Project;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Generation\ProjectGenerationService;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * Générateur de projets pour la commande make:project:new.
 *
 * Ce générateur utilise le ProjectGenerationService pour créer un projet complet
 * et affiche les événements via le MessageDisplayAdapter.
 *
 * @template T of TypeContainerInterface
 */
final class MakeEnvProject extends AbstractMaker
{
    public function __construct(
        private readonly Project                       $projectEnvironment,
        private readonly FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private readonly ProjectGenerationService      $projectGenerationService,

        private readonly SluggerInterface              $slugger,
    )
    {
    }

    public static function getCommandName(): string
    {
        return 'make:project:new';
    }

    public static function getCommandDescription(): string
    {
        return 'Create news project';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addOption('client', 'c', InputOption::VALUE_REQUIRED, 'Client name')
            ->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'Project name');

        if (($file = file_get_contents(__DIR__ . '/MakeEnvProject.txt')) !== false) {
            $command
                ->setHelp($file);
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml',
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $io->title('Project Création');

        // Get client name from option or ask interactively
        $clientOption = $input->getOption('client');
        $projectOption = $input->getOption('project');
        $this->projectEnvironment->setClient($this->slugger->slug((string)$clientOption)->toString());
        $this->projectEnvironment->setProject($this->slugger->slug((string)$projectOption)->toString());

        $loadedProject = $this->fileSystemEnvironmentServices->loadEnvironments($this->projectEnvironment);
        Assert::isInstanceOf($loadedProject, Project::class);
        $this->projectGenerationService->generateCompleteProject($loadedProject);

    }
}
