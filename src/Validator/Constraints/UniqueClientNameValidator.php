<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Form\Model\ClientModel;
use App\Normalizer\ClientNameNormalizer;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Webmozart\Assert\Assert;

/**
 * Validateur pour s'assurer que le nom du client est unique.
 *
 * Ce validateur implémente le pattern Strategy pour vérifier qu'aucun
 * dossier client avec le nom normalisé n'existe déjà.
 */
class UniqueClientNameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ClientNameNormalizer $clientNameNormalizer,
        private readonly FileSystemEnvironmentServices $environmentServices,
    ) {
    }

    /**
     * Valide l'unicité du nom de client.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueClientName) {
            throw new UnexpectedTypeException($constraint, UniqueClientName::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        try {
            // Récupérer le modèle client depuis le contexte pour vérifier le mode édition
            /** @var ClientModel $clientModel */
            $clientModel = $value;
            Assert::isInstanceOf($clientModel, ClientModel::class);
            // Normaliser le nom du client
            $normalizedName = $this->clientNameNormalizer->normalize($clientModel->getClient());
            $originalClient = $clientModel->getOriginalClient();

            // Vérifier si un dossier avec ce nom existe déjà
            if ($this->clientDirectoryExists($normalizedName, $originalClient)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ client_name }}', $normalizedName)
                    ->addViolation();
            }
        } catch (\Exception) {
            // En cas d'erreur système de fichiers, signaler l'erreur
            $this->context->buildViolation($constraint->filesystemErrorMessage)
                ->addViolation();
        }
    }

    /**
     * Vérifie si un dossier client existe déjà.
     *
     * @param string|null $originalClient Nom original du client en mode édition
     */
    private function clientDirectoryExists(string $clientName, ?string $originalClient = null): bool
    {
        // Obtenir la liste des clients existants via EnvironmentServices
        $existingClients = $this->environmentServices->getFolder(FileSystemEnvironmentServices::PROJECT_ROOT_FOLDER_IN_DOCKER);
        // Vérifier si le nom normalisé existe déjà (insensible à la casse)
        foreach ($existingClients as $existingClient) {
            if (strtolower($existingClient) === strtolower($clientName)) {
                // En mode édition, ignorer le nom original
                if (null !== $originalClient && strtolower($existingClient) === strtolower($originalClient)) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }
}
