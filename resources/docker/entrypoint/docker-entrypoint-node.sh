#!/bin/bash
set -e

dirname="/var/www/html/projects/${CLIENT}/${PROJECT}/${FOLDER_NAME}"
cd "$dirname"

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ];
then
	set -- node "$@"
fi

## Detect package manager
detect_package_manager() {
    if [[ -f "pnpm-lock.yaml" ]]; then
        echo "pnpm"
    elif [[ -f "yarn.lock" ]]; then
        echo "yarn"
    elif [[ -f "package-lock.json" ]]; then
        echo "npm"
    else
        echo "npm"  # default
    fi
}

# Detect and export package manager for use in entrypoint-addon.sh
export PACKAGE_MANAGER=$(detect_package_manager)

if [[ -f package.json ]]
then
    ## Install dependencies only in dev mode, in production install is done in Dockerfile
    if [[ "$DOCKER_ENV" == "dev" ]]; then
        echo "🔧 Install dependencies"
        $PACKAGE_MANAGER install

    fi
fi

# Ensure node_modules/.bin has correct permissions
if [[ -d "node_modules/.bin" ]]; then
    echo "🔧 Ensure node_modules/.bin has correct permissions"
    find node_modules/.bin -type f -exec chmod +x {} \;
    find node_modules/.bin -type l -exec chmod +x {} \;
fi


if [[ "$ENABLE_LOCAL_SERVER" == "local" && -d "$dirname" && "$DOCKER_ENV" == "dev" ]];
then
    # Start development server based on framework
    if [[ -f "package.json" ]]; then
        # Detect which dev command exists in package.json
        if grep -q '"dev"' package.json; then
            echo "🔧 Start next development server"
            $PACKAGE_MANAGER run dev
        elif grep -q '"start:dev"' package.json; then
            echo "🔧 Start nest development server"
            $PACKAGE_MANAGER run start:dev
        else
            echo "Warning: No dev or start:dev script found in package.json"
        fi
    fi
fi

## Exécuter le script entrypoint-addon.sh s'il existe
if [ -f "$dirname/bin/entrypoint-addon.sh" ]; then
  echo "🔧 Exécution de l'entrypoint additionnel..."
  source "$dirname/bin/entrypoint-addon.sh"
fi

echo "Project Started"
exec "$@"
