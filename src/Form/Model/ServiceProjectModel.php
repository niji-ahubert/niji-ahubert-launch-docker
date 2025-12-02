<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\DataStorage;
use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;

use App\Enum\ServiceVersion\VersionLaravelSupported;
use App\Enum\ServiceVersion\VersionMariadbSupported;
use App\Enum\ServiceVersion\VersionMysqlSupported;
use App\Enum\ServiceVersion\VersionNginxSupported;
use App\Enum\ServiceVersion\VersionNodeSupported;
use App\Enum\ServiceVersion\VersionPgsqlSupported;
use App\Enum\ServiceVersion\VersionPhpSupported;
use App\Enum\ServiceVersion\VersionReactSupported;
use App\Enum\ServiceVersion\VersionRedisSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use App\Enum\ServiceVersion\VersionSymfonySupported;
use App\Enum\WebServer;
use App\Validator\Constraints\ValidFrameworkLanguage;
use App\Validator\Constraints\ValidVersionFramework;
use App\Validator\Constraints\ValidVersionService;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidVersionService]
#[ValidVersionFramework]
#[ValidFrameworkLanguage]
class ServiceProjectModel
{
    private Uuid $id;

    #[Assert\Regex(
        pattern: '/^git@[a-zA-Z0-9\-\.]+:[a-zA-Z0-9\-\/]+\.git$/',
        message: 'validator.service.github_repository_format',
        match: true,
    )]
    private ?string $githubRepository = null;

    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\-_\/]+$/',
        message: 'validator.service.github_branch_format',
        match: true,
    )]
    private ?string $githubBranch = null;

    #[Assert\NotNull(message: 'validator.service.language_required')]
    private ?ProjectContainer $language = null;

    /**
     * @var FrameworkLanguageInterface|null
     * @phpstan-var FrameworkLanguageInterface<FrameworkLanguagePhp|FrameworkLanguageNode>|null
     */
    private ?FrameworkLanguageInterface $framework = null;


    /**
     * @phpstan-var VersionServiceSupportedInterface<VersionNodeSupported|VersionMariadbSupported|VersionPhpSupported|VersionMysqlSupported|VersionRedisSupported|VersionPgsqlSupported|VersionNginxSupported>|null
     */
    #[Assert\NotNull(message: 'validator.service.version_required')]
    private ?VersionServiceSupportedInterface $versionService = null;

    /**
     * @phpstan-var VersionFrameworkSupportedInterface<VersionSymfonySupported|VersionReactSupported|VersionLaravelSupported>|null
     */
    private ?VersionFrameworkSupportedInterface $versionFramework = null;

    #[Assert\NotBlank(message: 'validator.service.folder_name_required')]
    private ?string $folderName = null;

    /** @var string[]|null|null */
    private ?array $extensionsRequired = [];

    #[Assert\NotBlank(message: 'validator.service.url_service_required')]
    #[Assert\Regex(
        pattern: '/\.docker\.localhost$/',
        message: 'validator.service.url_service_docker_localhost',
    )]
    private ?string $urlService = null;

    #[Assert\NotNull(message: 'validator.service.webserver_required')]
    #[Assert\Choice(
        callback: [WebServer::class, 'cases'],
        message: 'validator.service.webserver_choice',
    )]
    private ?WebServer $webServer = null;

//    #[Assert\All([
//        new Assert\Choice(
//            callback: [DataStorage::class, 'cases'],
//            message: 'validator.service.data_storage_choice'
//        )
//    ])]
    /** @var DataStorage[]|null $dataStorages */
    private ?array $dataStorages = [];

    public function __construct()
    {
        $this->id = Uuid::v7();
    }


    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): ServiceProjectModel
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return DataStorage[]|null
     */
    public function getDataStorages(): ?array
    {
        return $this->dataStorages;
    }

    /**
     * @param DataStorage[]|null $dataStorages
     */
    public function setDataStorages(?array $dataStorages): self
    {
        $this->dataStorages = $dataStorages;
        return $this;
    }


    /**
     * @phpstan-return VersionServiceSupportedInterface<VersionNodeSupported|VersionMariadbSupported|VersionPhpSupported|VersionMysqlSupported|VersionRedisSupported|VersionPgsqlSupported|VersionNginxSupported>|null
     */
    public function getVersionService(): ?VersionServiceSupportedInterface
    {
        return $this->versionService;
    }

    public function getWebServer(): ?WebServer
    {
        return $this->webServer;
    }

    public function getUrlService(): ?string
    {
        return $this->urlService;
    }

    /** @return string[]|null */
    public function getExtensionsRequired(): ?array
    {
        return $this->extensionsRequired;
    }

    public function getFolderName(): ?string
    {
        return $this->folderName;
    }

    /**
     * @phpstan-return VersionFrameworkSupportedInterface<VersionSymfonySupported|VersionReactSupported|VersionLaravelSupported>|null
     */
    public function getVersionFramework(): ?VersionFrameworkSupportedInterface
    {
        return $this->versionFramework;
    }

    public function getGithubRepository(): ?string
    {
        return $this->githubRepository;
    }

    public function getLanguage(): ?ProjectContainer
    {
        return $this->language;
    }


    /**
     * @phpstan-return FrameworkLanguageInterface<FrameworkLanguagePhp|FrameworkLanguageNode>|null
     */
    public function getFramework(): ?FrameworkLanguageInterface
    {
        return $this->framework;
    }

    public function getGithubBranch(): ?string
    {
        return $this->githubBranch;
    }

    public function setGithubRepository(?string $githubRepository): self
    {
        $this->githubRepository = $githubRepository;

        return $this;
    }

    public function setLanguage(?ProjectContainer $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @phpstan-param FrameworkLanguageInterface<FrameworkLanguagePhp|FrameworkLanguageNode> $framework
     */
    public function setFramework(FrameworkLanguageInterface $framework): self
    {
        $this->framework = $framework;

        return $this;
    }

    /**
     * @phpstan-param VersionServiceSupportedInterface<VersionNodeSupported|VersionMariadbSupported|VersionPhpSupported|VersionMysqlSupported|VersionRedisSupported|VersionPgsqlSupported|VersionNginxSupported> $versionService
     */
    public function setVersionService(VersionServiceSupportedInterface $versionService): self
    {
        $this->versionService = $versionService;

        return $this;
    }

    /**
     * @phpstan-param VersionFrameworkSupportedInterface<VersionSymfonySupported|VersionReactSupported|VersionLaravelSupported> $versionFramework
     */
    public function setVersionFramework(VersionFrameworkSupportedInterface $versionFramework): self
    {
        $this->versionFramework = $versionFramework;

        return $this;
    }

    public function setFolderName(?string $folderName): self
    {
        $this->folderName = $folderName;

        return $this;
    }

    /** @param string[]|null $extensionsRequired */
    public function setExtensionsRequired(?array $extensionsRequired): self
    {
        $this->extensionsRequired = $extensionsRequired;

        return $this;
    }

    public function setUrlService(?string $urlService): self
    {
        $this->urlService = $urlService;

        return $this;
    }

    public function setWebServer(?WebServer $webServer): self
    {
        $this->webServer = $webServer;

        return $this;
    }

    public function setGithubBranch(?string $githubBranch): self
    {
        $this->githubBranch = $githubBranch;

        return $this;
    }
}
