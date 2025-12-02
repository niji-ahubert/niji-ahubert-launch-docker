<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte de validation pour les noms de dossiers.
 *
 * Cette contrainte utilise le pattern Constraint de Symfony pour valider
 * que le nom fourni peut être utilisé comme nom de dossier sur différents
 * systèmes d'exploitation.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute]
class ValidFolderName extends Constraint
{
    /**
     * Message d'erreur par défaut.
     */
    public string $message = 'validator.client.invalid_folder_name';

    /**
     * Message pour les caractères invalides.
     */
    public string $invalidCharactersMessage = 'validator.client.invalid_characters';

    /**
     * Message pour les noms réservés.
     */
    public string $reservedNameMessage = 'validator.client.reserved_name';

    /**
     * Message pour les noms commençant ou finissant par un point.
     */
    public string $dotMessage = 'validator.client.dot_not_allowed';

    /**
     * Message pour les noms vides ou contenant uniquement des espaces.
     */
    public string $emptyMessage = 'validator.client.empty_or_whitespace';
}
