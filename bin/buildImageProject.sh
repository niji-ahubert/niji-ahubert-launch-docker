#!/bin/bash
source bin/library/library.sh
source bin/library/gum.sh


display_message --text 'Build project'

display_message --text 'Choose your client'
selectClient

display_message --text "Client: $client
Choose your project to Build"
selectProject --client $client

display_message --text "Client: $client
Project: $project
Select application to Build"
selectApplication --client $client --project $project


if [ -n "$choosenFolder" ]; then
    IFS=' ' read -r -a startProject <<< "$choosenFolder"
else
   retrieveApplicationsByClient --pathClientProjects "projects/$client/$project"
   deleteAll=true
fi

IFS=' ' read -r -a startProject <<< "$choosenFolder"
loadSocleEnvVar --tmpProject "$absoluteProject"

for projectToBuild in "${startProject[@]}"
do


  env_file="$absoluteProject/config/$projectToBuild.env"
  source $env_file
  display_message --text "Building service : ${FOLDER_NAME}"

  buildImage --project "${PROJECT}" \
            --client "${CLIENT}" \
            --env "${DOCKER_ENV}" \
            --folder_name "${FOLDER_NAME}" \
            --service_type "${SERVICE_TYPE}" \
            --env_file "${env_file}"

  display_message --text "Docker image for service ${FOLDER_NAME} has been rebuilt"

done
