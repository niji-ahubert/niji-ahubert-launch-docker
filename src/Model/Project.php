<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\Environment;
use App\Model\Service\AbstractContainer;
use Symfony\Component\Validator\Constraints as Assert;

final class Project
{
    public const TRAEFIK_NETWORK = 'public-dev';
    #[Assert\NotBlank]
    private string $client;
    private string $project;

    /**
     * @var AbstractContainer[]
     */
    private array $serviceContainer = [];
    private string $traefikNetwork = self::TRAEFIK_NETWORK;
    private Environment $environmentContainer = Environment::DEV;

    public function getClient(): string
    {
        return $this->client;
    }

    public function setClient(string $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function setProject(string $project): self
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return AbstractContainer[]
     */
    public function getServiceContainer(): array
    {
        return array_values($this->serviceContainer);
    }

    public function addServiceContainer(AbstractContainer $abstractContainer): void
    {
        $this->serviceContainer[] = $abstractContainer;
    }

    public function removeServiceContainer(AbstractContainer $abstractContainer): void
    {
        foreach ($this->serviceContainer as $key => $service) {
            if ($service->getId() === $abstractContainer->getId()) {
                unset($this->serviceContainer[$key]);
                $this->serviceContainer = array_values($this->serviceContainer);

                return;
            }
        }
    }

    /**
     * @param AbstractContainer[] $serviceContainer
     */
    public function setServiceContainer(array $serviceContainer): self
    {
        $this->serviceContainer = array_values($serviceContainer);

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
