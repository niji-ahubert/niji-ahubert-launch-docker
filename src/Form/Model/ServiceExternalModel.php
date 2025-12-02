<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Model\Service\AbstractContainer;
use App\Validator\Constraints\UniqueServiceEnum;
use App\Validator\Constraints\UniqueServiceNameEnum;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class ServiceExternalModel
{
    private Uuid $id;

    #[Assert\NotBlank(message: 'validator.service.not_blank')]
    #[UniqueServiceEnum]
    #[UniqueServiceNameEnum]
    private ?string $serviceName = null;

    #[Assert\NotBlank(message: 'validator.service.version_not_blank')]
    private ?string $version = null;

    /** @var AbstractContainer[]|null */
    private ?array $allServices = [];

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    
    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): ServiceExternalModel
    {
        $this->id = $id;
        return $this;
    }


    /** @return AbstractContainer[]|null */
    public function getAllServices(): ?array
    {
        return $this->allServices;
    }

    /** @param AbstractContainer[]|null $allServices */
    public function setAllServices(?array $allServices): self
    {
        $this->allServices = $allServices;

        return $this;
    }

    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    public function setServiceName(?string $serviceName): self
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }
}
