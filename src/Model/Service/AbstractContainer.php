<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\ContainerType\ServiceContainer;
use App\Enum\DataStorage;
use App\Enum\ServiceVersion\VersionMariadbSupported;
use App\Enum\ServiceVersion\VersionMysqlSupported;
use App\Enum\ServiceVersion\VersionNginxSupported;
use App\Enum\ServiceVersion\VersionNodeSupported;
use App\Enum\ServiceVersion\VersionPgsqlSupported;
use App\Enum\ServiceVersion\VersionPhpSupported;
use App\Enum\ServiceVersion\VersionRedisSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Uid\Uuid;

#[AutoconfigureTag()]
#[DiscriminatorMap(
    typeProperty: 'serviceContainer',
    mapping: [
        ServiceContainer::MYSQL->value => ContainerMysql::class,
        ServiceContainer::MARIADB->value => ContainerMariadb::class,
        ServiceContainer::PGSQL->value => ContainerPgsql::class,
        ServiceContainer::REDIS->value => ContainerRedis::class,
        ServiceContainer::NGINX->value => ContainerNginx::class,
        ProjectContainer::PHP->value => ContainerPhp::class,
        ProjectContainer::NODE->value => ContainerNode::class,
    ],
)]
abstract class AbstractContainer implements \Stringable
{
    protected ?string $folderName = null;
    protected ProjectContainer|ServiceContainer $serviceContainer;
    protected ?string $dockerServiceName = null;
    protected ?string $dockerVersionService = null;

    /** @var string[]|null */
    protected ?array $versionSupported = null;

    /** @var string[]|null */
    protected ?array $frameworkSupported = null;

    /** @var string[]|null */
    protected ?array $extensionSupported = null;

    /** @var string[]|null */
    protected ?array $webserverSupported = null;

    /** @var array<string, string[]>|null */
    protected ?array $extensionsRequired = [];
    private Uuid $id;

    /** @var string[]|null */
    private ?array $extensionContainer = null;
    private ?AbstractFramework $framework = null;
    private ?WebServer $webServer = null;

    /** @var DataStorage[]|null */
    private ?array $dataStorages = [];
    private ?string $urlService = null;
    private ?string $githubRepository = null;
    private ?string $githubBranch = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function __toString(): string
    {
        return $this->serviceContainer->value;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): self
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

    abstract public function getFormType(): string;

    /**
     * @phpstan-return VersionServiceSupportedInterface<VersionMariadbSupported|VersionMysqlSupported|VersionNginxSupported|VersionNodeSupported|VersionPgsqlSupported|VersionPhpSupported|VersionRedisSupported>|null
     */
    abstract public function getVersionServiceEnum(): ?VersionServiceSupportedInterface;

    public function getFolderName(): ?string
    {
        return $this->folderName;
    }

    public function setFolderName(?string $folderName): self
    {
        $this->folderName = $folderName;

        return $this;
    }

    public function getDockerVersionService(): ?string
    {
        return $this->dockerVersionService;
    }

    public function setDockerVersionService(string $dockerVersionService): self
    {
        $this->dockerVersionService = $dockerVersionService;

        return $this;
    }

    public function getDockerServiceName(): ?string
    {
        return $this->dockerServiceName;
    }

    public function setDockerServiceName(?string $dockerServiceName): self
    {
        $this->dockerServiceName = $dockerServiceName;

        return $this;
    }

    public function getGithubRepository(): ?string
    {
        return $this->githubRepository;
    }

    public function setGithubRepository(?string $githubRepository): self
    {
        $this->githubRepository = $githubRepository;

        return $this;
    }

    public function getGithubBranch(): ?string
    {
        return $this->githubBranch;
    }

    public function setGithubBranch(?string $githubBranch): self
    {
        $this->githubBranch = $githubBranch;

        return $this;
    }

    public function support(string $serviceContainer): bool
    {
        return $this->serviceContainer::tryFrom($serviceContainer) === $this->getServiceContainer();
    }

    /** @return array<string, string[]>|null */
    public function getExtensionsRequired(): ?array
    {
        return $this->extensionsRequired;
    }

    /** @param array<string, string[]>|null $extensionsRequired */
    public function setExtensionsRequired(?array $extensionsRequired): self
    {
        $this->extensionsRequired = $extensionsRequired;

        return $this;
    }

    /** @return string[]|null */
    #[Ignore]
    public function getWebserverSupported(): ?array
    {
        return $this->webserverSupported;
    }

    public function getUrlService(): ?string
    {
        return $this->urlService;
    }

    public function setUrlService(?string $urlService): self
    {
        $this->urlService = $urlService;

        return $this;
    }

    /** @return string[]|null */
    #[Ignore]
    public function getFrameworkSupported(): ?array
    {
        return $this->frameworkSupported;
    }

    /** @return string[]|null */
    #[Ignore]
    public function getExtensionSupported(): ?array
    {
        return $this->extensionSupported;
    }

    public function getServiceContainer(): ServiceContainer|ProjectContainer
    {
        return $this->serviceContainer;
    }

    /** @return string[]|null */
    #[Ignore]
    public function getVersionSupported(): ?array
    {
        return $this->versionSupported;
    }

    /** @return string[]|null */
    public function getExtensionContainer(): ?array
    {
        return $this->extensionContainer;
    }

    public function addExtensionContainer(string $extension): self
    {
        if (null === $this->extensionContainer || !\in_array($extension, $this->extensionContainer, true)) {
            $this->extensionContainer[] = $extension;
        }

        return $this;
    }

    /** @param string[] $extensionContainer */
    public function setExtensionContainer(?array $extensionContainer): self
    {
        $this->extensionContainer = $extensionContainer;

        return $this;
    }

    public function getFramework(): ?AbstractFramework
    {
        return $this->framework;
    }

    public function setFramework(AbstractFramework $framework): self
    {
        $this->framework = $framework;

        return $this;
    }

    public function getWebServer(): ?WebServer
    {
        return $this->webServer;
    }

    public function setWebServer(?WebServer $webServer): self
    {
        $this->webServer = $webServer;

        return $this;
    }

    public function getName(): string
    {
        return $this->serviceContainer->value;
    }

    public function getVersion(): ?string
    {
        return $this->dockerVersionService;
    }
}
