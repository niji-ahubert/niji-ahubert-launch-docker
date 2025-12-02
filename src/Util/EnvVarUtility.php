<?php

namespace App\Util;

final readonly class EnvVarUtility
{
    /**
     * @param string $envFile
     * @return array<string, string|int>
     */
    public static function loadEnvironmentVariables(string $envFile): array
    {
        $envVars = [];

        if (!is_readable($envFile)) {
            return $envVars;
        }

        $lines = file($envFile, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);

        if (false === $lines) {
            return $envVars;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Ignorer les commentaires et les lignes vides
            if (str_starts_with($line, '#')) {
                continue;
            }
            if ('' === $line) {
                continue;
            }

            // Parser les variables au format KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $envVars[trim($key)] = trim($value, '"\'');
            }
        }

        return $envVars;
    }
}