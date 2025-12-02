#!/bin/bash
################################# load socle.json
function loadSocleEnvVar() {
  parseArguments "$@"

  if [ -z "$tmpProject" ]; then
    tmpProject=$(gum file projects --directory --height=10)
  fi
  configFolder=$tmpProject/config
  if [ ! -f "$configFolder/socle.json" ]; then
     gum style \
     	--foreground 212 --border-foreground 240 --border double \
     	--align center --width 50 --margin "1 2" --padding "2 4" \
     	'Your project not exist ! '
     exit;
  fi

  # Lire le fichier JSON et extraire les valeurs nécessaires
  json_content=$(cat "$configFolder/socle.json")

  # Extraire les valeurs principales
  client=$(echo "$json_content" | jq -r '.client')
  project=$(echo "$json_content" | jq -r '.project')
  traefik_network=$(echo "$json_content" | jq -r '.traefikNetwork')
  env=$(echo "$json_content" | jq -r '.environmentContainer')

  # Récupérer tous les services PHP et Node en format JSON
  services=$(echo "$json_content" | jq -c '.serviceContainer[] | select(.serviceContainer=="php" or .serviceContainer=="node")')

  # Vérifier si des services ont été trouvés
  if [ -z "$services" ]; then
    display_message --text "No PHP or Node services found in the project"
    exit 1
  fi

  # Boucler sur tous les services
  echo "$services" | while read -r service_json; do
    # Extraire les informations pour chaque service
    service_type=$(echo "$service_json" | jq -r '.serviceContainer')
    phpversion=$(echo "$service_json" | jq -r '.versionLanguageContainer')
    port_number=$(echo "$service_json" | jq -r '.webServer.portWebServer')
    url_website=$(echo "$service_json" | jq -r '.urlService')
    enable_quality=$(echo "$service_json" | jq -r '.framework.hasQualityTools')
    folder_name=$(echo "$service_json" | jq -r '.folderName')
    framework_name=$(echo "$service_json" | jq -r '.framework.name')
    framework_version=$(echo "$service_json" | jq -r '.framework.frameworkVersion')
    index_folder=$(echo "$service_json" | jq -r '.framework.folderIndex')
    should_use_composer=$(echo "$service_json" | jq -r '.framework.useComposer')
    enable_local_server=$(echo "$service_json" | jq -r '.webServer.webServer')

    # Déterminer le chemin du fichier .env attendu pour ce service (lecture uniquement)
    env_file="$tmpProject/config/${folder_name}.env"

    # Ne plus générer/écraser le fichier .env. On se contente de vérifier son existence et d'exporter quelques infos utiles.
    if [ ! -f "${env_file}" ]; then
      display_message --text "Missing env file: ${env_file}. Please create it manually."
      continue
    fi

    # Exporter des variables de contexte pour un usage ultérieur (sans écrire de fichiers)
    : # no-op; place holder to keep loop structure intact
  done

  export client project traefik_network env service_type folder_name
}


################################# Build Dockerfile
function buildImage {
  parseArguments "$@"


  if [ -n "$no_cache" ]; then
    NO_CACHE="--no-cache"
  else
    NO_CACHE=""
  fi
 # source "${env_file}"

  if [[ $env == "dev" ]]; then
    # Premier build
   PHP_VERSION=${PHP_VERSION} DOCKER_ENV=${env} URL_LOCAL_WEBSITE=${URL_LOCAL_WEBSITE} docker --log-level=ERROR compose --project-name stack-builder-project -f docker-compose.admin.yml build $NO_CACHE build-php-$env  || exit 1
  fi



  # Second build
  service_name="${CLIENT}-${PROJECT}-${FOLDER_NAME}-${env}"
  echo "--- SERVICE NAME --- $service_name"

  docker --log-level=ERROR compose \
    --env-file "${env_file}" \
    --project-name "stack-builder-project" \
    -f "projects/${CLIENT}/${PROJECT}/docker-compose.yml" \
    build $NO_CACHE "${service_name}" || exit 1
}


parseArguments() {
  while [[ $# -gt 0 ]]; do
    if [[ $1 == --* ]]; then
      param_name=$(echo "$1" | sed 's/--//g')
      param_value="$2"

      # Créer la variable d'environnement correspondant au paramètre
      export "$param_name"="$param_value"

      shift 2
    else
      echo "Paramètre inconnu: $1"
      shift
    fi
  done
}
