# Validation des noms de clients

Ce document décrit le système de validation des noms de clients implémenté dans l'application.

## Vue d'ensemble

Le système de validation des noms de clients garantit que :
1. Les noms respectent les contraintes de format pour les noms de dossiers
2. Les noms sont uniques (aucun dossier client existant avec le même nom)
3. Les noms sont normalisés de manière cohérente

## Composants

### 1. Contraintes de validation

#### `ValidFolderName`
- **Localisation** : `src/Validator/Constraints/ValidFolderName.php`
- **Fonction** : Valide que le nom peut être utilisé comme nom de dossier
- **Vérifications** :
  - Caractères autorisés : `a-zA-Z0-9_-`
  - Pas de noms réservés Windows (CON, PRN, AUX, etc.)
  - Pas de points en début ou fin
  - Pas d'espaces uniquement

#### `UniqueClientName`
- **Localisation** : `src/Validator/Constraints/UniqueClientName.php`
- **Fonction** : Vérifie l'unicité du nom de client
- **Vérifications** :
  - Compare avec les dossiers existants dans le répertoire projects
  - Insensible à la casse
  - Utilise le nom normalisé pour la comparaison

### 2. Service de normalisation

#### `ClientNameNormalizer`
- **Localisation** : `src/Service/ClientNameNormalizer.php`
- **Fonction** : Normalise les noms de clients en utilisant Symfony String
- **Fonctionnalités** :
  - Conversion en ASCII (translittération des accents)
  - Remplacement des espaces par des tirets
  - Suppression des caractères non autorisés
  - Limitation à 50 caractères
  - Génération de suggestions alternatives

### 3. Endpoints AJAX

#### `/client/suggest`
- **Méthode** : GET
- **Paramètre** : `q` (chaîne de recherche)
- **Réponse** :
```json
{
    "suggestions": ["suggestion-1", "suggestion-2", ...],
    "normalized": "nom-normalise"
}
```

#### `/client/check-availability`
- **Méthode** : GET
- **Paramètre** : `name` (nom à vérifier)
- **Réponse** :
```json
{
    "available": true|false,
    "message": "Message traduit",
    "normalized": "nom-normalise"
}
```

## Utilisation

### Dans un formulaire Symfony

```php
use App\Form\Model\ClientModel;

$clientModel = new ClientModel();
$clientModel->setClient('Mon Nouveau Client');

$violations = $validator->validate($clientModel);
// Vérifie automatiquement le format et l'unicité
```

### Validation manuelle

```php
use App\Service\ClientNameNormalizer;

$normalizer = $container->get(ClientNameNormalizer::class);

// Normaliser un nom
$normalized = $normalizer->normalize('Société Française & Co.');
// Résultat : "societe-francaise-co"

// Vérifier la validité
$isValid = $normalizer->isValid('mon-client-2024');

// Générer des alternatives
$alternatives = $normalizer->generateAlternatives('Mon Client');
// Résultat : ["mon-client-1", "mon-client-2", ...]
```

### Validation côté client (JavaScript)

Le fichier `assets/js/client-form.js` fournit une validation en temps réel :

- Vérification de disponibilité pendant la saisie
- Suggestions automatiques
- Affichage du nom normalisé
- Messages d'erreur traduits

## Messages de traduction

### Français (`messages.fr.yaml`)
```yaml
client:
  validation:
    name:
      not_blank: "Le nom du client ne peut pas être vide."
      min_length: "Le nom du client doit contenir au moins {{ limit }} caractères."
      max_length: "Le nom du client ne peut pas dépasser {{ limit }} caractères."
      invalid_characters: "Le nom du client ne peut contenir que des lettres, chiffres, tirets (-) et underscores (_)."
      reserved_name: "Ce nom est réservé par le système et ne peut pas être utilisé."
      already_exists: "Un client avec le nom \"{{ client_name }}\" existe déjà."
      filesystem_error: "Erreur lors de la vérification de l'unicité du nom."
```

### Anglais (`messages.en.yaml`)
Messages équivalents en anglais.

## Tests

### Tests unitaires
- `tests/Unit/Validator/Constraints/ValidFolderNameValidatorTest.php`
- `tests/Unit/Validator/Constraints/UniqueClientNameValidatorTest.php`
- `tests/Unit/Service/ClientNameNormalizerTest.php`
- `tests/Unit/Form/Model/ClientModelTest.php`

### Tests d'intégration
- `tests/Integration/Form/ClientModelIntegrationTest.php`

### Tests fonctionnels
- `tests/Functional/Controller/ClientControllerTest.php`

## Exemples de normalisation

| Entrée | Sortie normalisée |
|--------|-------------------|
| `"Société Française SARL"` | `"societe-francaise-sarl"` |
| `"Jean-Pierre & Associés"` | `"jean-pierre-associes"` |
| `"Microsoft Corporation Inc."` | `"microsoft-corporation-inc"` |
| `"Müller & Co. 中文"` | `"muller-co"` |
| `"ABC   Company   2024"` | `"abc-company-2024"` |

## Configuration

### Services (automatiquement configurés)
- `ClientNameNormalizer` : Injecte `SluggerInterface`
- `ValidFolderNameValidator` : Injecte `ClientNameNormalizer` et `EnvironmentServices`
- `UniqueClientNameValidator` : Injecte `ClientNameNormalizer` et `EnvironmentServices`

### Contraintes appliquées automatiquement
Les contraintes sont appliquées via les attributs PHP sur la propriété `$client` du modèle `ClientModel`.

## Sécurité

- **Validation côté serveur** : Toutes les validations sont effectuées côté serveur
- **Échappement** : Les noms sont échappés dans les templates
- **Caractères sûrs** : Seuls les caractères alphanumériques, tirets et underscores sont autorisés
- **Noms réservés** : Protection contre les noms réservés du système d'exploitation
