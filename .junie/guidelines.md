# Configuration du projet PHP avec Symfony 7.x

Ce projet utilise :

- Symfony 7.x (version LTS) comme framework principal.
- PHPStan (niveau max) pour l’analyse statique : `phpstan.dist.neon`. Documentation : [https://phpstan.org/user-guide/getting-started](https://phpstan.org/user-guide/getting-started)
- Rector pour le refactoring automatisé : `rector.php`. Documentation : [https://getrector.com/documentation](https://getrector.com/documentation)
- PHP-CS-Fixer pour le formatage : `.php-cs-fixer.dist.php`
- PSR-12 comme convention de codage.

---

# Contrainte principale

**Toute génération de code, de configuration ou de structure doit exclusivement s’appuyer sur la documentation officielle de Symfony 7.x.**

Référence : [https://symfony.com/doc/7.x](https://symfony.com/doc/7.x)

- Ne jamais utiliser d’API, de composants ou de syntaxe non présente ou obsolète par rapport à la documentation officielle.
- Favoriser les composants Symfony plutôt que des bibliothèques tierces.

---

# Documentation automatique des fonctions (PHPDoc)

- Chaque fonction ou méthode générée doit avoir un bloc PHPDoc clair et complet :
    - `@param`, `@return`, `@throws` si applicable
    - Description concise en français
    - Typage strict (éviter `mixed`)

Exemple :

```php
/**
 * Crée une nouvelle commande pour un utilisateur donné.
 *
 * @param User $user
 * @param array $produits
 * @return Commande
 */
public function creerCommande(User $user, array $produits): Commande
```

---

# Standards de code

- `declare(strict_types=1);`
- Formatage PSR-12 via PHP-CS-Fixer
- Aucun warning avec PHPStan (niveau max)
- Code conforme à Rector (UP_TO_PHP_81)
- Nom des variables, classes et méthodes explicite et cohérent

---

# Structure du projet

- `src/` pour la logique applicative
- `tests/` pour les tests automatisés
- Namespace PSR-4
- Architecture DDD légère encouragée

---

# Bonnes pratiques Symfony 7.x

- Utiliser les attributs PHP (`#[Route]`, `#[AsController]`, etc.)
- Favoriser l’injection de dépendances
- Respecter les conventions du Framework pour les services, formulaires, events, repositories, etc.
- Suivre les recommandations de sécurité de Symfony (Voter, UserChecker, PasswordHasher…)

---

# Pattern de conception

**Tout code généré doit correspondre à un design pattern connu et approprié au contexte.**

- Singleton, Factory, Strategy, Observer, DTO, Service Layer, etc.
- Chaque choix de structure ou classe doit pouvoir être justifié par un usage classique reconnu.
- Éviter tout “code magique” ou sur-généralisé qui ne suit pas une logique claire de pattern.
- Favoriser les patterns utilisés par Symfony (Event Subscriber, DataTransformer, etc.)

---

---

# Tests Automatisés

- Utiliser PHPUnit (intégré à Symfony) pour tous les tests.
- Rédiger des tests unitaires pour la logique métier (services, entités, etc.) dans le répertoire `tests/Unit/`.
- Rédiger des tests d'intégration et fonctionnels pour les contrôleurs, services et l'application dans son ensemble dans `tests/Integration/` et `tests/Functional/`.
- Viser une couverture de code significative pour les fonctionnalités critiques. L'objectif est la qualité et la robustesse, pas un pourcentage arbitraire.
- Utiliser des factories (par exemple, avec `symfony/maker-bundle` ou `zenstruck/foundry`) pour la création de données de test afin d'assurer des tests cohérents et maintenables.
- Les tests doivent respecter les mêmes standards de code que l'application (PSR-12, PHPStan, Rector).

---

# Gestion des Dépendances

- Toute nouvelle dépendance externe (bundle Composer, bibliothèque JS, etc.) doit être justifiée par un besoin clair et validée collectivement si possible.
- Privilégier les composants Symfony existants ou les bibliothèques largement reconnues, activement maintenues et sécurisées.
- Vérifier la compatibilité de version avec Symfony 7.x et PHP 8.1+ avant d'ajouter une dépendance.
- Utiliser Composer pour la gestion des dépendances PHP. Les fichiers `composer.json` et `composer.lock` doivent être versionnés.
- Exécuter régulièrement `composer outdated` et `symfony security:check` (ou `composer audit`) pour identifier les dépendances obsolètes ou vulnérables et planifier leurs mises à jour.
- Éviter d'inclure des dépendances de développement (`require-dev`) dans les builds de production.

---
# Exécution des commandes

- Toutes les commandes (ex: tests PHPUnit, scripts Symfony, commandes Composer, etc.) doivent être exécutées à l'intérieur du conteneur Docker 'php' en utilisant WSL. La commande complète doit commencer par : `wsl docker compose -f docker-compose.yml -f docker-compose-local.yml exec php [commande_a_executer]`. Cette commande doit être lancée depuis la racine du projet.

---

# Résumé

- Code généré 100 % conforme à la documentation officielle Symfony 7.x
- Documenté, typé, refactorable, testé, formaté
- Structuré selon des patterns reconnus
- Intégration parfaite avec les outils PHPStan, Rector, PHP-CS-Fixer
- Structure PSR-4
- Bonnes pratiques Symfony 7.x
- Gestion des dépendances avec Composer et Symfony
- Tests unitaires, fonctionnels, d'intégration et de qualité
- Support de PHP 8.3 et Symfony 7.x avec PHPStan et PHP-CS-Fixer
- Code conforme aux recommandations de Symfony pour les services, formulaires, events, repositories, etc.
- Documentation automatique des fonctions (PHPDoc)
- Standards de code
- Structure du projet
- Bonnes pratiques Symfony 7.x
- Pattern de conception
- Tests Automatisés
- Gestion des Dépendances
- Résumé
