<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Enum\Environment;
use App\Model\Project;
use App\Validator\Constraints as CustomAssert;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[CustomAssert\UniqueClientProject(groups: ['Default', 'Project'])]
class ProjectModel
{
    private Uuid $id;

    #[Assert\NotBlank(groups: ['Default', 'Project'])]
    private ?string $client = null;

    #[Assert\NotBlank(groups: ['Default', 'Project'])]
    private ?string $project = null;

    #[Assert\NotBlank]
    private string $traefikNetwork = Project::TRAEFIK_NETWORK;

    #[Assert\NotBlank]
    private Environment $environmentContainer = Environment::DEV;

    /** @var array<string, string|null>|null */
    private ?array $originalProjectData = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }


    /**
     * @return array<string, string|null>|null
     */
    public function getOriginalProjectData(): ?array
    {
        return $this->originalProjectData;
    }

    public function setOriginalProjectData(?string $client, ?string $project): self
    {
        $this->originalProjectData = [
            'client' => $client,
            'project' => $project,
        ];

        return $this;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(?string $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    public function setProject(?string $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getTraefikNetwork(): string
    {
        return $this->traefikNetwork;
    }

    public function setTraefikNetwork(string $traefikNetwork): self
    {
        $this->traefikNetwork = $traefikNetwork;

        return $this;
    }

    public function getEnvironmentContainer(): Environment
    {
        return $this->environmentContainer;
    }

    public function setEnvironmentContainer(Environment $environmentContainer): self
    {
        $this->environmentContainer = $environmentContainer;

        return $this;
    }
}
