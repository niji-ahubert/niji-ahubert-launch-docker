<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validateur pour les noms de dossiers.
 *
 * Ce validateur implémente le pattern Strategy pour valider les noms
 * de dossiers selon les règles des systèmes de fichiers courants.
 */
class ValidFolderNameValidator extends ConstraintValidator
{
    /**
     * Noms réservés par Windows.
     */
    private const array RESERVED_NAMES = [
        'CON', 'PRN', 'AUX', 'NUL',
        'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
        'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9',
    ];

    /**
     * Caractères interdits dans les noms de fichiers/dossiers.
     */
    private const array FORBIDDEN_CHARACTERS = ['<', '>', ':', '"', '/', '\\', '|', '?', '*'];

    /**
     * Valide le nom de dossier.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidFolderName) {
            throw new UnexpectedTypeException($constraint, ValidFolderName::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Vérifier si le nom est vide ou contient uniquement des espaces
        if ('' === trim($value)) {
            $this->context->buildViolation($constraint->emptyMessage)->addViolation();

            return;
        }

        // Vérifier les caractères interdits
        if ($this->containsForbiddenCharacters($value)) {
            $this->context->buildViolation($constraint->invalidCharactersMessage)->addViolation();

            return;
        }

        // Vérifier les noms réservés
        if ($this->isReservedName($value)) {
            $this->context->buildViolation($constraint->reservedNameMessage)->addViolation();

            return;
        }

        // Vérifier les points au début ou à la fin
        if ($this->startsOrEndsWithDot($value)) {
            $this->context->buildViolation($constraint->dotMessage)->addViolation();

            return;
        }

        // Vérifier que le nom contient uniquement des caractères autorisés
        if (!$this->hasValidCharacters($value)) {
            $this->context->buildViolation($constraint->invalidCharactersMessage)->addViolation();

            return;
        }
    }

    /**
     * Vérifie si le nom contient des caractères interdits.
     */
    private function containsForbiddenCharacters(string $value): bool
    {
        return array_any(self::FORBIDDEN_CHARACTERS, fn ($char): bool => str_contains($value, (string) $char));
    }

    /**
     * Vérifie si le nom est un nom réservé.
     */
    private function isReservedName(string $value): bool
    {
        $upperValue = strtoupper($value);

        return array_any(self::RESERVED_NAMES, fn ($reserved): bool => $upperValue === $reserved || str_starts_with($upperValue, $reserved.'.'));
    }

    /**
     * Vérifie si le nom commence ou finit par un point.
     */
    private function startsOrEndsWithDot(string $value): bool
    {
        return str_starts_with($value, '.') || str_ends_with($value, '.');
    }

    /**
     * Vérifie que le nom ne contient que des caractères valides.
     */
    private function hasValidCharacters(string $value): bool
    {
        return 1 === preg_match('/^[a-zA-Z0-9_-]+$/', $value);
    }
}
