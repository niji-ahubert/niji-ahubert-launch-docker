# Stack Builder Project

Ce projet est un outil de gestion de stack Docker pour le développement web, permettant de créer et gérer facilement des environnements de développement avec Traefik comme reverse proxy.

## Fonctionnalités principales

- Création et gestion de projets PHP et Node.js avec Docker
- Intégration avec Traefik pour le routage
- Environnement de développement isolé par projet
- CLI Symfony pour la gestion des projets
- Interface web pour la création de nouveaux projets

## Prérequis

- Docker
- Docker Compose
- Gum (installé automatiquement via le Makefile)

## Installation

1. Clonez le repository
2. Exécutez la commande suivante pour configurer l'environnement de développement :
```bash
make setup
```

Cette commande exécute automatiquement les étapes suivantes :
```bash
make install-deps    # Installe les dépendances système (gum)
make setup-env       # Génère le fichier .env depuis .env.dist  
make setup-node      # Installe les dépendances Node.js
make setup-assets    # Configure les assets Symfony
make start-web-new-project  # Démarre les services
```

## Utilisation

### Commandes principales

- `make setup` : Configuration complète de l'environnement de développement
- `make start project=<folder>` : Démarre un projet existant
- `make create-project` : Crée un nouveau projet
- `make delete` : Supprime un projet existant
- `make stop` : Arrête la stack Docker
- `make bash-php` : Ouvre un shell bash dans le conteneur PHP du projet actuel

### Commandes CLI Symfony

- `make build-cli-sf` : Recompile l'image CLI Symfony
- `make bash-cli-sf` : Ouvre un shell bash dans le conteneur CLI Symfony
- `make bash-new-project` : Crée un nouveau projet via la CLI Symfony
- `make start-web-new-project` : Démarre l'interface web pour la création de projets
- `make stop-web-new-project` : Arrête l'interface web

### Commandes utilitaires

- `make requirement` : Vérifie les prérequis
- `make install-deps` : Installe les dépendances système
- `make build-image` : Recompile l'image du projet  
- `make traefik_up` : Vérifie l'état de Traefik

### Commandes de setup modulaires

- `make setup-env` : Génère uniquement le fichier .env
- `make setup-node` : Installe uniquement les dépendances Node.js
- `make setup-assets` : Configure uniquement les assets Symfony

## Architecture technique

Le projet utilise une architecture basée sur les **patterns de conception** (Strategy, Factory, Chain of Responsibility) pour la création d'applications selon différentes technologies (PHP, Symfony, Laravel, Node.js).

📚 **Documentation technique complète** : Voir [ARCHITECTURE.md](./docs/ARCHITECTURE.md) pour les détails d'implémentation, l'extensibilité et les références aux classes.

## Structure du projet

Le projet utilise une architecture basée sur Docker Compose avec :
- Un conteneur PHP pour l'application
- Un conteneur CLI Symfony pour la gestion des projets
- Traefik comme reverse proxy
- Une interface web pour la création de projets

## Aide

Pour voir toutes les commandes disponibles avec leurs descriptions :
```bash
make help
``` 
