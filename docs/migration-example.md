# Exemple de migration : SymfonyCreateStepHandler

Ce document montre étape par étape comment migrer un handler complexe vers le nouveau système unifié.

## Code original (AVANT)

```php
<?php

declare(strict_types=1);

namespace App\Strategy\Step;

use App\Model\Project;use App\Model\Service\AbstractContainer;use Symfony\Component\Console\Style\SymfonyStyle;use Symfony\Component\Process\Exception\ProcessFailedException;use Symfony\Component\Process\Process;

final readonly class SymfonyCreateStepHandler extends \App\Strategy\Step\AbstractServiceStepHandler
{
    public function handle(SymfonyStyle $io, AbstractContainer $serviceContainer, Project $project): \Generator
    {
        $io->section('🎵 Création du projet Symfony');
        yield '🎵 Création du projet Symfony'; // DUPLICATION #1

        $this->fileSystemEnvironmentServices->loadEnvironments($project);
        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($serviceContainer);
        
        if ($this->fileSystemEnvironmentServices->isDirectoryEmpty($applicationProjectPath) === false) {
            yield 'Le dossier de destination est vide, on ne fait rien'; // DUPLICATION #2
            return;
        }

        yield '📦 Lancement de composer create-project...'; // DUPLICATION #3

        $process = new Process([
            'composer',
            'create-project',
            'symfony/skeleton',
            basename($applicationProjectPath),
            '--no-interaction',
        ], dirname($applicationProjectPath));

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            $io->error($error);
            yield 'Erreur: ' . $error; // DUPLICATION #4
            throw new ProcessFailedException($process);
        }

        $io->success('✅ Projet Symfony créé avec succès');
        yield '✅ Projet Symfony créé avec succès'; // DUPLICATION #5
    }

    // ... autres méthodes
}
```

## Problèmes identifiés

1. **5 duplications** de messages (yield + $io)
2. **Couplage fort** avec SymfonyStyle
3. **Impossible à streamer** en web sans modification
4. **Difficile à tester** unitairement

## Code migré (APRÈS)

```php
<?php

declare(strict_types=1);

namespace App\Strategy\Step;

use App\Model\Project;use App\Model\ServerEventModel;use App\Model\Service\AbstractContainer;use Symfony\Component\Console\Style\SymfonyStyle;use Symfony\Component\Process\Exception\ProcessFailedException;use Symfony\Component\Process\Process;
// ✅ AJOUT

final readonly class SymfonyCreateStepHandler extends AbstractServiceStepHandler
{
    public function handle(SymfonyStyle $io, AbstractContainer $serviceContainer, Project $project): \Generator
    {
        // ✅ Un seul yield, pas de $io->section()
        yield new ServerEventModel(
            type: 'start',
            message: '🎵 Création du projet Symfony',
            timestamp: date('Y-m-d H:i:s'),
            level: 'info'
        );

        $this->fileSystemEnvironmentServices->loadEnvironments($project);
        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($serviceContainer);
        
        // ✅ Cas spécial : warning avec return
        if ($this->fileSystemEnvironmentServices->isDirectoryEmpty($applicationProjectPath) === false) {
            yield new ServerEventModel(
                type: 'log',
                message: 'Le dossier de destination n\'est pas vide, opération annulée',
                timestamp: date('Y-m-d H:i:s'),
                level: 'warning'
            );
            return;
        }

        // ✅ Message informatif
        yield new ServerEventModel(
            type: 'log',
            message: '📦 Lancement de composer create-project...',
            timestamp: date('Y-m-d H:i:s'),
            level: 'info'
        );

        $process = new Process([
            'composer',
            'create-project',
            'symfony/skeleton',
            basename($applicationProjectPath),
            '--no-interaction',
        ], dirname($applicationProjectPath));

        $process->setTimeout(300);
        $process->run();

        // ✅ Gestion d'erreur enrichie
        if (!$process->isSuccessful()) {
            yield new ServerEventModel(
                type: 'error',
                message: 'Erreur lors de la création du projet Symfony',
                timestamp: date('Y-m-d H:i:s'),
                level: 'error',
                error: $process->getErrorOutput(),
                exitCode: $process->getExitCode()
            );
            
            throw new ProcessFailedException($process);
        }

        // ✅ Message de succès
        yield new ServerEventModel(
            type: 'complete',
            message: '✅ Projet Symfony créé avec succès',
            timestamp: date('Y-m-d H:i:s'),
            level: 'info',
            exitCode: 0
        );
    }

    // ... autres méthodes inchangées
}
```

## Changements détaillés

### 1. Import ajouté

```php
use App\Model\ServerEventModel;
```

### 2. Message de démarrage

**Avant** :

```php
$io->section('🎵 Création du projet Symfony');
yield '🎵 Création du projet Symfony';
```

**Après** :

```php
yield new ServerEventModel(
    type: 'start',           // Type spécial pour début d'étape
    message: '🎵 Création du projet Symfony',
    timestamp: date('Y-m-d H:i:s'),
    level: 'info'
);
```

### 3. Message d'avertissement avec early return

**Avant** :

```php
if (...) {
    yield 'Le dossier de destination est vide, on ne fait rien';
    return;
}
```

**Après** :

```php
if (...) {
    yield new ServerEventModel(
        type: 'log',
        message: 'Le dossier de destination n\'est pas vide, opération annulée',
        timestamp: date('Y-m-d H:i:s'),
        level: 'warning'  // ✅ Niveau approprié pour un skip
    );
    return;
}
```

### 4. Message informatif

**Avant** :

```php
yield '📦 Lancement de composer create-project...';
```

**Après** :

```php
yield new ServerEventModel(
    type: 'log',
    message: '📦 Lancement de composer create-project...',
    timestamp: date('Y-m-d H:i:s'),
    level: 'info'
);
```

### 5. Gestion d'erreur enrichie

**Avant** :

```php
if (!$process->isSuccessful()) {
    $error = $process->getErrorOutput();
    $io->error($error);
    yield 'Erreur: ' . $error;
    throw new ProcessFailedException($process);
}
```

**Après** :

```php
if (!$process->isSuccessful()) {
    yield new ServerEventModel(
        type: 'error',              // Type explicite
        message: 'Erreur lors de la création du projet Symfony',
        timestamp: date('Y-m-d H:i:s'),
        level: 'error',
        error: $process->getErrorOutput(),  // ✅ Détails dans field séparé
        exitCode: $process->getExitCode()   // ✅ Code de sortie pour debug
    );
    
    throw new ProcessFailedException($process);
}
```

### 6. Message de succès

**Avant** :

```php
$io->success('✅ Projet Symfony créé avec succès');
yield '✅ Projet Symfony créé avec succès';
```

**Après** :

```php
yield new ServerEventModel(
    type: 'complete',        // ✅ Type spécial pour succès final
    message: '✅ Projet Symfony créé avec succès',
    timestamp: date('Y-m-d H:i:s'),
    level: 'info',
    exitCode: 0              // ✅ Indique le succès explicitement
);
```

## Mapping des types

| Contexte      | Type       | Level     | Quand l'utiliser           |
|---------------|------------|-----------|----------------------------|
| Début d'étape | `start`    | `info`    | Premier message du handler |
| Info générale | `log`      | `info`    | Messages intermédiaires    |
| Avertissement | `log`      | `warning` | Conditions non-critiques   |
| Erreur        | `error`    | `error`   | Avant throw d'exception    |
| Succès final  | `complete` | `info`    | Dernier message du handler |

## Comportement de l'adaptateur

### En CLI (avec SymfonyStyle)

```php
// type: 'start' → $io->section(message)
🎵 Création du projet Symfony
===========================

// type: 'log', level: 'info' → $io->writeln()
📦 Lancement de composer create-project...

// type: 'error' → $io->error(message + error details)
[ERROR] Erreur lors de la création du projet Symfony
        
        Détails: ...error output...
        Code de sortie: 1

// type: 'complete' → $io->success(message)
[OK] ✅ Projet Symfony créé avec succès
```

### En Web SSE (streaming)

```javascript
// Tous les événements sont streamés en JSON
{
    "type"
:
    "start",
        "message"
:
    "🎵 Création du projet Symfony",
        "timestamp"
:
    "2025-10-27 17:14:47",
        "level"
:
    "info"
}

{
    "type"
:
    "log",
        "message"
:
    "📦 Lancement de composer create-project...",
        "timestamp"
:
    "2025-10-27 17:14:48",
        "level"
:
    "info"
}

{
    "type"
:
    "complete",
        "message"
:
    "✅ Projet Symfony créé avec succès",
        "timestamp"
:
    "2025-10-27 17:15:32",
        "level"
:
    "info",
        "exitCode"
:
    0
}
```

## Checklist de migration

Pour chaque handler, suivre ces étapes :

- [ ] **1. Ajouter import** : `use App\Model\ServerEventModel;`
- [ ] **2. Identifier toutes les duplications** (yield + $io)
- [ ] **3. Remplacer le premier message** par type `start`
- [ ] **4. Remplacer les messages intermédiaires** par type `log`
- [ ] **5. Enrichir les erreurs** avec `error` field et `exitCode`
- [ ] **6. Remplacer le dernier message** par type `complete`
- [ ] **7. Supprimer tous les appels** `$io->section()`, `$io->error()`, `$io->success()`
- [ ] **8. Garder les throw** pour les exceptions
- [ ] **9. Tester** : La commande CLI doit fonctionner sans changement visible
- [ ] **10. Vérifier** : Aucun message dupliqué

## Test de validation

Après migration, exécuter :

```bash
php bin/console make:project:new --client=test-client --project=test-symfony
```

**Vérifier** :

- ✅ Les messages s'affichent dans le bon ordre
- ✅ Aucun message n'est dupliqué
- ✅ Les erreurs sont bien formatées
- ✅ Le projet se crée correctement

## Temps estimé

**Par handler** : 10-15 minutes

- Lecture et analyse : 3min
- Modifications : 5min
- Test : 2min
- Validation : 2min

## Prochains handlers à migrer

Suivre le même pattern pour :

1. LaravelCreateStepHandler (similaire à Symfony)
2. NodeInitStepHandler (similaire à Composer)
3. FolderProjectCreateStepHandler (plus de conditions)
4. DockerFileStepHandler (génération de fichiers)
5. EnvFileCreateStepHandler (simple)
6. StartPagePhpStepHandler (simple)
7. NpmStepHandler (très simple)
8. ComposerInstallStepHandler (similaire à ComposerInit)

## Support

En cas de doute :

- Consulter `ComposerInitStepHandler.php` (déjà migré)
- Voir `docs/architecture/unified-event-system.md`
- Vérifier le mapping des types ci-dessus
