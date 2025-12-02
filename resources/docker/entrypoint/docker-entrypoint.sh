#!/bin/bash
set -e

reloadXdebug=false
if [ "$XDEBUG_MODE" = "debug" ]
then
  reloadXdebug=true
  export XDEBUG_MODE="off"
fi

dirname="/var/www/html/projects/${CLIENT}/${PROJECT}/${FOLDER_NAME}"
cd "$dirname"

if [[ -f composer.json ]]
then
    ## Install vendor only dev mode in production install is done in Dockerfile
    composer install
fi

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ];
then
	set -- php "$@"
fi

if [ $reloadXdebug == true ]
then
  export XDEBUG_MODE="debug"
fi

if [[ "$ENABLE_LOCAL_SERVER" == "local" && -d "$dirname" &&  "$DOCKER_ENV" == "dev" ]];
then

  if [ -z "$INDEX_FOLDER" ]; then
    php -S 0.0.0.0:"$PORT_NUMBER" -c "$dirname"/php.ini
  else
    php -S 0.0.0.0:"$PORT_NUMBER" -c "$dirname"/php.ini -t "$INDEX_FOLDER/"
  fi

fi

if [ $# -eq 0 ]; then
  set -- php-fpm
fi

# Exécuter le script entrypoint-addon.sh s'il existe
if [ -f "$dirname/bin/entrypoint-addon.sh" ]; then
  echo "🔧 Exécution de l'entrypoint additionnel..."
  source "$dirname/bin/entrypoint-addon.sh"
fi

echo "Project Started"
exec "$@"
