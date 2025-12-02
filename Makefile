# Misc
.DEFAULT_GOAL = help
.PHONY        = help
DOCKER_COMP = docker  --log-level=ERROR compose --project-name generator-socle
DOCKER_EXEC = $(DOCKER_COMP) exec


## —— 🎵 🐳 PHP stack Makefile 🐳 🎵 ——————————————————————————————————
install-deps: ## Install required packages
	@echo "📦 Vérification et installation des dépendances système"
	@if command -v gum >/dev/null 2>&1; then \
		echo "✅ gum est déjà installé"; \
	else \
		echo "🔧 Installation de gum..."; \
		sudo mkdir -p /etc/apt/keyrings; \
		curl -fsSL https://repo.charm.sh/apt/gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/charm.gpg; \
		echo "deb [signed-by=/etc/apt/keyrings/charm.gpg] https://repo.charm.sh/apt/ * *" | sudo tee /etc/apt/sources.list.d/charm.list; \
		sudo apt update && sudo apt install -y gum; \
		echo "✅ gum installé avec succès"; \
	fi

requirement: ## Check and validate requirements
	@./bin/requirement.sh

setup: install-deps setup-env setup-node setup-assets start-web-new-project ## Setup development environment for new developers
	@echo "🎉 Setup terminé avec succès!"
	@echo "🌐 Votre application est disponible sur: https://niji-generator.docker.localhost"
	@echo "📝 N'oubliez pas de configurer vos clés API OpenAI dans le fichier .env"

start: requirement traefik_up stop ## Start an existing project: make start project=<folder>
	@./bin/startProject.sh  || exit 1

create-project: requirement bash-new-project build-image ## Create a new project

build-image: requirement ## Rebuild image project
	@./bin/buildImageProject.sh

delete: requirement stop ## Delete an existing project
	@./bin/deleteProject.sh

stop: ## Stop the docker-compose stack
	@$(DOCKER_COMP) down --remove-orphans

bash-php: ## Start a bash shell in your current project
	@./bin/bashPhp.sh

traefik_up: ## Start and check Traefik health
	@./bin/healthcheckTraefik.sh

build-cli-sf: ## Build CLI PHP container for admin
	docker --log-level=ERROR compose -f docker-compose.admin.yml build cli-php

build-webserver-sf: ## Build webserver PHP container for admin
	docker --log-level=ERROR compose -f docker-compose.admin.yml build webserver-php

bash-cli-sf: ## Start a bash shell in CLI PHP container
	 docker --log-level=ERROR compose -f docker-compose.admin.yml run --rm cli-php bash

bash-new-project: ## Start a bash shell in webserver for new project
	 docker --log-level=ERROR compose --profile webserver --profile webserver -f docker-compose.admin.yml  exec webserver-php bash

start-web-new-project: ## Start webserver for new project development
	docker --log-level=ERROR compose --profile webserver -f docker-compose.admin.yml up --detach

stop-web-new-project: ## Stop the docker-compose stack
	docker --log-level=ERROR compose --profile webserver -f docker-compose.admin.yml down --remove-orphans

setup-env: ## Generate .env file from .env.dist
	@echo "📄 Génération du fichier .env depuis .env.dist"
	@if [ ! -f ".env.dist" ]; then \
		echo "❌ Erreur: Le fichier .env.dist n'existe pas"; \
		exit 1; \
	fi
	@if [ -f ".env" ]; then \
		echo "⚠️  Le fichier .env existe déjà, il sera conservé"; \
	else \
		cp .env.dist .env; \
		echo "✅ Fichier .env généré avec succès"; \
	fi

setup-node: ## Install Node.js dependencies
	@echo "📦 Installation des dépendances Node.js"
	@if [ -z "$$(docker images -q generator-socle-webserver-theme 2> /dev/null)" ]; then \
		echo "🔨 Construction du container Node.js"; \
		$(DOCKER_COMP) -f docker-compose.admin.yml build webserver-theme; \
	fi
	@echo "🚀 Démarrage du container Node.js"
	@$(DOCKER_COMP) -f docker-compose.admin.yml --profile node up webserver-theme -d
	@echo "📥 Installation des packages npm"
	@$(DOCKER_COMP) -f docker-compose.admin.yml exec webserver-theme npm install
	@echo "✅ Dépendances Node.js installées"

setup-assets: ## Setup Symfony assets
	@echo "🎨 Configuration des assets Symfony"
	@if [ -z "$$(docker images -q generator-socle-webserver-php 2> /dev/null)" ]; then \
		echo "🔨 Construction du container PHP"; \
		$(DOCKER_COMP) -f docker-compose.admin.yml build webserver-php; \
	fi
	@echo "🚀 Démarrage du webserver PHP et Nginx"
	@$(DOCKER_COMP) -f docker-compose.admin.yml --profile webserver up -d
	@echo "📥 Installation des dépendances Composer"
	@$(DOCKER_COMP) -f docker-compose.admin.yml exec webserver-php composer install
	@echo "🎨 Construction des styles Tailwind CSS"
	@$(DOCKER_COMP) -f docker-compose.admin.yml exec webserver-php bin/console tailwind:build
	@echo "🔧 Compilation des assets"
	@$(DOCKER_COMP) -f docker-compose.admin.yml exec webserver-php bin/console asset-map:compile
	@echo "🗂️ Installation d'importmap"
	@$(DOCKER_COMP) -f docker-compose.admin.yml exec webserver-php bin/console importmap:install
	@echo "✅ Assets Symfony configurés"

run-qa: ## Run quality-gates scripts
	#docker --log-level=ERROR compose --profile webserver --profile webserver -f docker-compose.admin.yml  exec webserver-php -e XDEBUG_MODE=off php vendor/bin/swiss-knife finalize-classes src tests;
	docker --log-level=ERROR compose --profile webserver --profile webserver -f docker-compose.admin.yml exec -e XDEBUG_MODE=off webserver-php php vendor/bin/rector process --config rector.php;
	docker --log-level=ERROR compose --profile webserver --profile webserver -f docker-compose.admin.yml exec -e XDEBUG_MODE=off webserver-php php vendor/bin/php-cs-fixer fix --config .php-cs-fixer.dist.php --diff;
	docker --log-level=ERROR compose --profile webserver --profile webserver -f docker-compose.admin.yml exec -e XDEBUG_MODE=off webserver-php php vendor/bin/phpstan analyse --configuration phpstan.neon --memory-limit=-1;
	#$(EXEC_SECURITY) --exit-code 1 fs --scanners vuln /app;

help: ## Show this help message
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
