#!/bin/bash
source bin/library/library.sh
source bin/library/gum.sh


display_message --text 'Start project'

# Check if client and project are passed as arguments
if [ -n "$1" ] && [ -n "$2" ]; then
    client="$1"
    project="$2"
    display_message --text "Client and project provided:
Client: $client
Project: $project"

    # Validate that the project path exists
    if [ ! -d "projects/$client/$project" ]; then
        gum style \
            --foreground 212 --border-foreground 212 --border rounded \
            --align center --width 50 --margin "1 2" --padding "2 4" \
            "Error: Project path 'projects/$client/$project' not found."
        exit 1
    fi
else
    display_message --text 'Choose your client'
    selectClient

    display_message --text "Client: $client
Choose your project to Start"
    selectProject --client $client
fi

display_message --text "Client: $client
Project: $project
Select application to Start"
selectApplication --client $client --project $project

if [ -n "$choosenFolder" ]; then
    IFS=' ' read -r -a startProject <<< "$choosenFolder"
else
   retrieveApplicationsByClient --pathClientProjects "projects/$client/$project"
   deleteAll=true
fi

IFS=' ' read -r -a startProject <<< "$choosenFolder"
loadSocleEnvVar --tmpProject "$absoluteProject"

display_message --text "Starting external service"
docker --log-level=ERROR compose --profile runner-dev --project-name stack-builder-project \
-f "$absoluteProject/docker-compose.yml" \
up --detach

for projectToStart in "${startProject[@]}"
do
    env_file="$absoluteProject/config/$projectToStart.env"
    source $env_file

    if [ -z "$(docker images -q project-${CLIENT}-${PROJECT}-${FOLDER_NAME}-${DOCKER_ENV} 2> /dev/null)" ]; then
      gum style \
        --foreground 212 --border-foreground 212 --border rounded \
        --align center --width 50 --margin "1 2" --padding "2 4" \
        'Docker file not exist please launch make create_project'
      exit;
    fi

    display_message --text "Starting service: ${FOLDER_NAME}"
    docker --log-level=ERROR compose --env-file "$env_file" --project-name stack-builder-project \
     -f "projects/${CLIENT}/${PROJECT}/docker-compose.yml" \
     up --detach "${CLIENT}-${PROJECT}-${FOLDER_NAME}-${DOCKER_ENV}"

    display_message --text "Project ${FOLDER_NAME} has been launched"

    if [[ ${FRAMEWORK} == "symfony" && ${DOCKER_ENV} == "dev" && ${SERVICE_TYPE} == "php" ]]
    then
      display_message --align "left" --text "TO ACTIVATE DEBUG BAR you must add in
                              config/package/framework.yml
                              when@dev:
                                  framework:
                                      trusted_proxies: '%env(TRUSTED_PROXIES)%'
                                      trusted_headers:
                                          - 'x-forwarded-for'
                                          - 'x-forwarded-host'
                                          - 'x-forwarded-proto'
                                          - 'x-forwarded-port'
                                          - 'x-forwarded-prefix'
                              "
    fi
done

display_message --align "left" --text  "your ${service_type} service ${folder_name} is launched in ${env} mode
                              On error see your status container in https://portainer.docker.localhost
                              Open your website with https://${url_website}
#                               "
