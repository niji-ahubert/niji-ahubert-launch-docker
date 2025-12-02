<?php
declare(strict_types=1);

namespace App\Attribute;


/**
 * Attribut pour marquer un paramètre Project comme devant être enrichi automatiquement.
 *
 * Utilisé avec ProjectValueResolver pour charger automatiquement les données complètes du projet.
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
final class EnrichedProject
{
}