<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use App\Strategy\Step\AbstractServiceStepHandler;
use App\Util\DockerUtility;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

final readonly class DockerFileServiceStepHandler extends AbstractBuildServiceStepHandler
{
    private AbstractContainer $currentContainer;

    public function __construct(
        private Filesystem            $filesystem,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        private Generator             $makerGenerator,
        private NormalizerInterface   $serializer,
        MercureService                $mercureService,
        ProcessRunnerService          $processRunner,

    )
    {

        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $this->currentContainer = $serviceContainer;

        $this->mercureService->initialize($project, LoggerChannel::START);
        $this->mercureService->dispatch(
            message: '🐋 Création du Dockerfile',
            type: TypeLog::START
        );

        $dockerfilePath = $this->fileSystemEnvironmentServices->getApplicationDockerfilePath($project, $serviceContainer);

        $variables = DockerUtility::getDockerfileVariable($serviceContainer, $project);
        $normalizeVar = $this->serializer->normalize($variables);
        Assert::isArray($normalizeVar);

        if ($this->filesystem->exists($dockerfilePath)) {
            $this->mercureService->dispatch(
                message: '📝 Mise à jour du Dockerfile...'
            );
            $this->updateDockerfile($dockerfilePath, $normalizeVar);
        } else {
            $this->mercureService->dispatch(
                message: '📄 Génération du Dockerfile...'
            );
            $this->makerGenerator->generateFile(
                targetPath: $dockerfilePath,
                templateName: $this->fileSystemEnvironmentServices->getDockerSkeletonFile($serviceContainer),
                variables: $normalizeVar,
            );
            $this->makerGenerator->writeChanges();
        }


        $this->mercureService->dispatch(
            message: '✅ Dockerfile généré avec success',
            type: TypeLog::COMPLETE,
            exitCode: 0
        );


    }

    /**
     * Met à jour un Dockerfile existant en préservant le code personnalisé entre les balises.
     *
     * Processus :
     * 1. Extrait le code personnalisé entre les balises ## Custom code Here ... ## et ## End Custom code ##
     * 2. Régénère le Dockerfile depuis le template avec les nouvelles variables
     * 3. Réinjecte le code personnalisé entre les balises dans le nouveau Dockerfile
     *
     * @param string $dockerfilePath Chemin du Dockerfile à mettre à jour
     * @param array $variables Variables pour la génération du template
     */
    private function updateDockerfile(string $dockerfilePath, array $variables): void
    {
        // Étape 1 : Extraire le code personnalisé de l'ancien Dockerfile
        $customCode = $this->extractCustomCode($dockerfilePath);

        // Étape 2 : Régénérer le Dockerfile depuis le template (mode CREATE)
        $this->createDockerfile($dockerfilePath, $variables);

        // Étape 3 : Réinjecter le code personnalisé si présent
        if ($customCode !== null) {
            $this->injectCustomCode($dockerfilePath, $customCode);
        }
    }

    /**
     * Extrait le code personnalisé entre les balises du Dockerfile.
     *
     * @param string $dockerfilePath Chemin du Dockerfile
     * @return string|null Le code personnalisé ou null si aucun code trouvé
     */
    private function extractCustomCode(string $dockerfilePath): ?string
    {
        $content = file_get_contents($dockerfilePath);
        if ($content === false) {
            return null;
        }

        // Regex pour capturer le contenu entre les balises
        $pattern = '/## Custom code Here \.\.\.\s*##\s*\n(.*?)\n\s*## End Custom code ##/s';

        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Crée un nouveau Dockerfile depuis le template.
     *
     * @param string $dockerfilePath Chemin du Dockerfile à créer
     * @param array $variables Variables pour la génération du template
     */
    private function createDockerfile(string $dockerfilePath, array $variables): void
    {
        // Supprime le fichier existant si présent
        if ($this->filesystem->exists($dockerfilePath)) {
            $this->filesystem->remove($dockerfilePath);
        }
        
        // Génère le nouveau Dockerfile depuis le template
        $this->makerGenerator->generateFile(
            targetPath: $dockerfilePath,
            templateName: $this->fileSystemEnvironmentServices->getDockerSkeletonFile($this->getCurrentContainer()),
            variables: $variables,
        );
        $this->makerGenerator->writeChanges();
    }

    /**
     * Injecte le code personnalisé entre les balises du Dockerfile.
     *
     * @param string $dockerfilePath Chemin du Dockerfile
     * @param string $customCode Code personnalisé à injecter
     */
    private function injectCustomCode(string $dockerfilePath, string $customCode): void
    {
        $content = file_get_contents($dockerfilePath);
        if ($content === false) {
            return;
        }

        // Remplace le contenu vide entre les balises par le code personnalisé
        $pattern = '/(## Custom code Here \.\.\.\s*##)\s*\n\s*(## End Custom code ##)/s';
        $replacement = "$1\n" . $customCode . "\n$2";

        $updatedContent = preg_replace($pattern, $replacement, $content);

        if ($updatedContent !== null) {
            $this->makerGenerator->dumpFile($dockerfilePath, $updatedContent);
            $this->makerGenerator->writeChanges();
        }
    }

    /**
     * Récupère le container actuellement traité (nécessaire pour getDockerSkeletonFile).
     *
     * @return AbstractContainer
     * @throws \RuntimeException Si le container n'a pas été initialisé
     */
    private function getCurrentContainer(): AbstractContainer
    {
        if ($this->currentContainer === null) {
            throw new \RuntimeException('Container not initialized. Call __invoke() first.');
        }

        return $this->currentContainer;
    }

    public static function getPriority(): int
    {
        return 1;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::DOCKERFILE;
    }


}
