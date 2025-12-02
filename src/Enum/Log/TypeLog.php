<?php

declare(strict_types=1);

namespace App\Enum\Log;

/**
 * Types de logs pour le système unifié d'événements.
 *
 * Basé sur la documentation migration-example.md, section "Mapping des types".
 * Chaque type correspond à un contexte d'utilisation spécifique dans les handlers.
 */
enum TypeLog: string
{
    /**
     * Type utilisé pour le début d'une étape.
     * Premier message du handler.
     */
    case START = 'start';

    /**
     * Type utilisé pour les messages informatifs généraux.
     * Messages intermédiaires pendant l'exécution.
     */
    case LOG = 'log';

    /**
     * Type utilisé pour les erreurs.
     * Avant le throw d'une exception.
     */
    case ERROR = 'error';

    /**
     * Type utilisé pour le succès final d'une étape.
     * Dernier message du handler en cas de succès.
     */
    case COMPLETE = 'complete';
}