<?php

declare(strict_types=1);

namespace App\Model\Service;

use App\Enum\Environment;
use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\ServiceVersion\VersionFrameworkSupportedInterface;
use App\Enum\ServiceVersion\VersionLaravelSupported;
use App\Enum\ServiceVersion\VersionReactSupported;
use App\Enum\ServiceVersion\VersionSymfonySupported;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap; // Change Annotation to Attribute
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Uid\Uuid;

#[AutoconfigureTag()]
#[DiscriminatorMap(
    typeProperty: 'type',
    mapping: [
        FrameworkLanguagePhp::PHP->value => FrameworkPhp::class,
        FrameworkLanguagePhp::LARAVEL->value => FrameworkLaravel::class,
        FrameworkLanguagePhp::SYMFONY->value => FrameworkSymfony::class,
    ],
)]
abstract class AbstractFramework
{
    /** @var FrameworkLanguageNode|FrameworkLanguagePhp */
    protected FrameworkLanguageInterface $name;

    /** @var string[]|null */
    protected ?array $frameworkVersionSupported = null;
    protected bool $useComposer = false;

    /** @var string[]|null */
    protected ?array $extensionsRequired = [];
    protected ?string $frameworkVersion = null;
    private readonly Uuid $id;
    private bool $hasQualityTools = false;
    private ?string $folderIndex = 'public';
    private Environment $applicationEnvironment = Environment::DEV;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    abstract public function support(string $frameworkChoose): bool;

    /**
     * @phpstan-return FrameworkLanguageInterface<FrameworkLanguagePhp|FrameworkLanguageNode>|null
     */
    abstract public function getFrameworkEnum(string $stringEnumValue): ?FrameworkLanguageInterface;

    /**
     * @phpstan-return VersionFrameworkSupportedInterface<VersionSymfonySupported|VersionReactSupported|VersionLaravelSupported>|null
     */
    abstract public function getVersionFrameworkEnum(): ?VersionFrameworkSupportedInterface;

    /** @return string[]|null */
    public function getExtensionsRequired(): ?array
    {
        return $this->extensionsRequired;
    }

    /** @param string[]|null $extensionsRequired */
    public function setExtensionsRequired(?array $extensionsRequired): self
    {
        $this->extensionsRequired = $extensionsRequired;

        return $this;
    }

    public function getFolderIndex(): ?string
    {
        return $this->folderIndex;
    }

    public function setFolderIndex(?string $folderIndex): self
    {
        $this->folderIndex = $folderIndex;

        return $this;
    }

    public function isUseComposer(): bool
    {
        return $this->useComposer;
    }

    public function setUseComposer(bool $useComposer): self
    {
        $this->useComposer = $useComposer;

        return $this;
    }

    /**
     * @return FrameworkLanguageNode|FrameworkLanguagePhp
     */
    public function getName(): FrameworkLanguageInterface
    {
        return $this->name;
    }

    /**
     * @param FrameworkLanguageNode|FrameworkLanguagePhp $name
     */
    public function setName(FrameworkLanguageInterface $name): self
    {
        $this->name = $name;

        return $this;
    }

    /** @return string[]|null */
    #[Ignore]
    public function getFrameworkVersionSupported(): ?array
    {
        return $this->frameworkVersionSupported;
    }

    /** @param string[]|null $frameworkVersionSupported */
    public function setFrameworkVersionSupported(?array $frameworkVersionSupported): self
    {
        $this->frameworkVersionSupported = $frameworkVersionSupported;

        return $this;
    }

    public function getFrameworkVersion(): ?string
    {
        return $this->frameworkVersion;
    }

    public function setFrameworkVersion(?string $frameworkVersion): self
    {
        $this->frameworkVersion = $frameworkVersion;

        return $this;
    }

    public function isHasQualityTools(): bool
    {
        return $this->hasQualityTools;
    }

    public function setHasQualityTools(bool $hasQualityTools): self
    {
        $this->hasQualityTools = $hasQualityTools;

        return $this;
    }

    public function getApplicationEnvironment(): Environment
    {
        return $this->applicationEnvironment;
    }

    public function setApplicationEnvironment(Environment $applicationEnvironment): self
    {
        $this->applicationEnvironment = $applicationEnvironment;

        return $this;
    }
}
