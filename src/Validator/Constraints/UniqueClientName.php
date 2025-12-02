<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute()]
class UniqueClientName extends Constraint
{
    /**
     * Message d'erreur par défaut.
     */
    public string $message = 'validator.client.name_already_exists';

    /**
     * Message pour les erreurs de système de fichiers.
     */
    public string $filesystemErrorMessage = 'validator.client.name_filesystem_error';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
