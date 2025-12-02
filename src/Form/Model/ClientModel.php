<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\Constraints\UniqueClientName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Modèle de formulaire pour la création et l'édition d'un client.
 *
 * Ce modèle utilise le pattern DTO (Data Transfer Object) pour encapsuler
 * les données du formulaire de création/édition de client.
 */
#[UniqueClientName(groups: ['Default', 'Project'])]
class ClientModel
{
    /**
     * Nom du client (utilisé comme nom de dossier).
     */
    #[Assert\NotBlank(message: 'validator.client.not_blank')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'validator.client.min_length',
        maxMessage: 'validator.client.max_length',
    )]
    private string $client = '';

    /**
     * Nom original du client (pour l'édition).
     * Utilisé pour identifier le dossier existant à renommer.
     */
    private ?string $originalClient = null;

    /**
     * Constructeur du modèle client.
     *
     * @param string|null $originalClient Nom original du client pour l'édition
     */
    public function __construct(?string $originalClient = null)
    {
        $this->originalClient = $originalClient;
        if (null !== $originalClient) {
            $this->client = $originalClient;
        }
    }

    /**
     * Retourne le nom du client.
     */
    public function getClient(): string
    {
        return $this->client;
    }

    /**
     * Définit le nom du client.
     */
    public function setClient(string $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Retourne le nom original du client.
     */
    public function getOriginalClient(): ?string
    {
        return $this->originalClient;
    }

    /**
     * Définit le nom original du client.
     */
    public function setOriginalClient(?string $originalClient): self
    {
        $this->originalClient = $originalClient;

        return $this;
    }

    /**
     * Indique si le modèle est en mode édition.
     */
    public function isEditing(): bool
    {
        return null !== $this->originalClient;
    }
}
