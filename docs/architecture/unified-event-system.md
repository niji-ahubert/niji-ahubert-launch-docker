# Système unifié de gestion des événements

## Vue d'ensemble

Ce document décrit l'architecture refactorisée pour unifier l'affichage des logs et événements dans l'application. L'objectif est d'avoir **un seul type de message** (ServerEventModel) qui peut être affiché dans différents contextes (CLI, Web SSE, logs fichiers, etc.).

## Problème résolu

### Avant la refactorisation ❌

```php
// Dans les handlers
yield '🎼 Initialisation du projet Composer';
$io->section('🎼 Initialisation du projet Composer'); // DUPLICATION!

// Dans les services
yield new ServerEventModel(...);
```

**Problèmes** :
- Duplication des messages
- Couplage fort avec SymfonyStyle
- Impossible de router vers différentes sorties
- Mélange de responsabilités

### Après la refactorisation ✅

```php
// Partout : yield uniquement ServerEventModel
yield new ServerEventModel(
    type: 'start',
    message: '🎼 Initialisation du projet Composer',
    timestamp: date('Y-m-d H:i:s'),
    level: 'info'
);

// L'affichage est délégué au MessageDisplayAdapter
$this->messageDisplayAdapter->displayToConsole($io, $event);
```

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Sources d'événements                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │ Step Handler │  │  Strategy    │  │   Service    │          │
│  │   (yield)    │  │   (yield)    │  │   (yield)    │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                 │                  │                   │
│         └─────────────────┴──────────────────┘                   │
│                           │                                      │
│                  yield ServerEventModel                         │
└───────────────────────────┼──────────────────────────────────────┘
                            ▼
           ┌────────────────────────────────────┐
           │   MessageDisplayAdapter             │
           │   (décide comment afficher)         │
           └────────────────┬───────────────────┘
                            │
              ┌─────────────┴─────────────┐
              │                           │
              ▼                           ▼
    ┌──────────────────┐        ┌──────────────────┐
    │  CLI Context     │        │  Web Context     │
    │  (SymfonyStyle)  │        │  (SSE Stream)    │
    │  - section()     │        │  - ServerEvent   │
    │  - writeln()     │        │  - JSON stream   │
    │  - error()       │        │                  │
    └──────────────────┘        └──────────────────┘
```

## Composants principaux

### 1. ServerEventModel (DTO)

Modèle de données unifié pour tous les événements.

```php
readonly class ServerEventModel
{
    public function __construct(
        private string $type,      // start, log, error, complete, etc.
        private string $message,   // Message principal
        private ?array $data,      // Données additionnelles
        private ?string $timestamp,
        private ?string $level,    // info, warning, error, debug
        private ?int $pid,
        private ?int $exitCode,
        private ?string $command,
        private ?string $error,
    ) {}
}
```

### 2. MessageDisplayAdapter (Service)

Service central qui adapte l'affichage selon le contexte.

**Responsabilités** :
- Affichage console avec SymfonyStyle
- Formatage des messages selon le type
- Support de multiples contextes d'affichage

**Méthodes principales** :
```php
// Affiche dans la console
public function displayToConsole(SymfonyStyle $io, ServerEventModel $event): void

// Consomme un générateur et affiche tous les événements
public function consumeAndDisplay(SymfonyStyle $io, \Generator $generator): void
```

### 3. Step Handlers (Strategy/Step)

Les handlers ne font plus d'affichage direct, ils yield uniquement des ServerEventModel.

**Avant** :
```php
public function handle(SymfonyStyle $io, ...): \Generator
{
    yield 'Message';
    $io->section('Message'); // DUPLICATION
}
```

**Après** :
```php
public function handle(SymfonyStyle $io, ...): \Generator
{
    yield new ServerEventModel(
        type: 'start',
        message: 'Message',
        timestamp: date('Y-m-d H:i:s'),
        level: 'info'
    );
}
```

### 4. CreateApplicationService

Service orchestrateur avec deux modes d'exécution :

#### Mode CLI (avec SymfonyStyle)
```php
public function __invoke(?SymfonyStyle $io, AbstractContainer $serviceContainer, Project $project): void
{
    // Consomme les événements et les affiche via l'adaptateur
    foreach ($strategy->execute($io, $serviceContainer, $project) as $event) {
        // Les événements sont déjà affichés dans execute()
    }
}
```

#### Mode Web SSE (sans SymfonyStyle)
```php
public function executeWithEvents(AbstractContainer $serviceContainer, Project $project): \Generator
{
    // Propage tous les événements pour le streaming web
    foreach ($strategy->execute(null, $serviceContainer, $project) as $event) {
        yield $event;
    }
}
```

## Flux d'exécution

### Contexte CLI (make:project:new)

```
1. MakeEnvProject::generate()
   ↓
2. ProjectGenerationService::generateCompleteProject()
   ↓ (yield ServerEventModel)
3. MessageDisplayAdapter::consumeAndDisplay()
   ↓
4. SymfonyStyle (console output)
```

### Contexte Web (SSE streaming)

```
1. DockerLogsController::streamGeneration()
   ↓
2. ProjectGenerationService::generateCompleteProject()
   ↓ (yield ServerEventModel)
3. ServerEventService::createServerEventFromModel()
   ↓
4. Response::stream() (SSE to browser)
```

## Avantages

### ✅ Séparation des responsabilités
- **Sources** : Génèrent uniquement des événements
- **Adaptateur** : Gère l'affichage selon le contexte
- **Modèle** : Structure de données uniquement

### ✅ Réutilisabilité
- Un même générateur fonctionne en CLI et Web
- Facile d'ajouter de nouveaux contextes (logs fichiers, etc.)

### ✅ Testabilité
- Pas de couplage avec SymfonyStyle
- Tests unitaires sur les événements générés
- Mocking simplifié de l'adaptateur

### ✅ Maintenabilité
- Plus de duplication de messages
- Code DRY
- Point central pour l'affichage

### ✅ Extensibilité
- Ajout facile de nouveaux types d'événements
- Support de nouveaux contextes d'affichage
- Formatage personnalisé par contexte

## Migration des handlers existants

### Étapes pour migrer un handler

1. **Ajouter l'import ServerEventModel**
```php
use App\Model\ServerEventModel;
```

2. **Remplacer les yield string par ServerEventModel**
```php
// Avant
yield '🎼 Message';

// Après
yield new ServerEventModel(
    type: 'log',
    message: '🎼 Message',
    timestamp: date('Y-m-d H:i:s'),
    level: 'info'
);
```

3. **Supprimer les appels directs à $io**
```php
// Avant
$io->section('Message');
$io->error('Erreur');
$io->success('Succès');

// Après
// Rien, c'est l'adaptateur qui gère l'affichage
```

4. **Garder les throws pour les erreurs critiques**
```php
if (!$process->isSuccessful()) {
    yield new ServerEventModel(type: 'error', ...);
    throw new ProcessFailedException($process);
}
```

### Types d'événements disponibles

- `start` : Début d'une étape
- `log` : Message informatif
- `error` : Erreur
- `complete` : Fin avec succès
- `process_started` : Processus démarré (avec PID)
- `custom` : Événement personnalisé

## Exemples d'utilisation

### Exemple 1 : Handler simple

```php
public function handle(SymfonyStyle $io, AbstractContainer $serviceContainer, Project $project): \Generator
{
    yield new ServerEventModel(
        type: 'start',
        message: '🎼 Début de l\'opération',
        timestamp: date('Y-m-d H:i:s'),
        level: 'info'
    );
    
    // Traitement...
    
    yield new ServerEventModel(
        type: 'log',
        message: 'Opération en cours...',
        timestamp: date('Y-m-d H:i:s'),
        level: 'info'
    );
    
    yield new ServerEventModel(
        type: 'complete',
        message: '✅ Opération terminée',
        timestamp: date('Y-m-d H:i:s'),
        level: 'info'
    );
}
```

### Exemple 2 : Avec gestion d'erreurs

```php
$process = new Process([...]);
$process->run();

if (!$process->isSuccessful()) {
    yield new ServerEventModel(
        type: 'error',
        message: 'Erreur lors de l\'exécution',
        timestamp: date('Y-m-d H:i:s'),
        level: 'error',
        error: $process->getErrorOutput(),
        exitCode: $process->getExitCode()
    );
    
    throw new ProcessFailedException($process);
}
```

### Exemple 3 : Consommation en CLI

```php
$generator = $this->projectGenerationService->generateCompleteProject($project);
$this->messageDisplayAdapter->consumeAndDisplay($io, $generator);
```

### Exemple 4 : Consommation en Web SSE

```php
#[Route('/stream-generation', methods: ['GET'])]
public function streamGeneration(Project $project): Response
{
    return new Response(function () use ($project) {
        $generator = $this->projectGenerationService->generateCompleteProject($project);
        
        foreach ($generator as $event) {
            $serverEvent = $this->serverEventService->createServerEventFromModel($event);
            echo $serverEvent;
            flush();
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ]);
}
```

## Checklist de migration

- [ ] Créer MessageDisplayAdapter
- [ ] Refactoriser AbstractProjectStrategy
- [ ] Refactoriser CreateApplicationService (ajouter executeWithEvents)
- [ ] Refactoriser ProjectGenerationService
- [ ] Refactoriser MakeEnvProject
- [ ] Migrer tous les Step Handlers
- [ ] Migrer tous les Docker Services
- [ ] Tester en CLI
- [ ] Tester en Web SSE
- [ ] Documentation mise à jour

## Fichiers concernés

### Services créés
- `src/Services/Generation/MessageDisplayAdapter.php`

### Services modifiés
- `src/Services/StrategyManager/CreateApplicationService.php`
- `src/Services/Generation/ProjectGenerationService.php`
- `src/Generator/MakeEnvProject.php`
- `src/Strategy/CreateApplication/AbstractProjectStrategy.php`

### Handlers à migrer
- `src/Strategy/Step/ComposerInitStepHandler.php` ✅
- `src/Strategy/Step/NodeInitStepHandler.php`
- `src/Strategy/Step/FolderProjectCreateStepHandler.php`
- `src/Strategy/Step/SymfonyCreateStepHandler.php`
- `src/Strategy/Step/LaravelCreateStepHandler.php`
- `src/Strategy/Step/DockerFileStepHandler.php`
- `src/Strategy/Step/StartPagePhpStepHandler.php`
- `src/Strategy/Step/EnvFileCreateStepHandler.php`
- Et tous les autres handlers...

## Bonnes pratiques

1. **Toujours yield ServerEventModel** : Jamais de string simple
2. **Pas d'appel direct à $io dans les handlers** : Laisser l'adaptateur gérer
3. **Utiliser les bons types d'événements** : start, log, error, complete
4. **Ajouter des timestamps** : Pour la traçabilité
5. **Inclure les détails d'erreur** : error field, exitCode, etc.
6. **Garder les exceptions** : Pour les erreurs critiques

## Tests

### Test unitaire d'un handler
```php
public function testHandlerYieldsCorrectEvents(): void
{
    $handler = new ComposerInitStepHandler(...);
    $generator = $handler->handle($io, $serviceContainer, $project);
    
    $events = iterator_to_array($generator);
    
    $this->assertInstanceOf(ServerEventModel::class, $events[0]);
    $this->assertEquals('start', $events[0]->getType());
}
```

### Test d'intégration avec l'adaptateur
```php
public function testMessageDisplayAdapterWithConsole(): void
{
    $event = new ServerEventModel(type: 'log', message: 'Test', ...);
    
    $this->adapter->displayToConsole($this->io, $event);
    
    // Vérifier que $io a reçu le bon appel
}
```

## Conclusion

Cette refactorisation unifie complètement la gestion des événements dans l'application. Tous les composants utilisent maintenant `ServerEventModel`, et l'affichage est délégué au `MessageDisplayAdapter`, permettant une séparation claire des responsabilités et une réutilisabilité maximale du code.
