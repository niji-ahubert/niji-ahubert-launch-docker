#!/bin/bash
source bin/library/library.sh
source bin/library/gum.sh
# Declare an array
MY_ENV=("dev" "prod")
tmpProjects=""

display_message --text 'Choose the project you wish to delete'
selectClientApplication --containerStart false --displayAllOption true

IFS=' ' read -r -a startProject <<< "$projectsLaunch"
for project in "${startProject[@]}"
do
  loadSocleEnvVar --tmpProject "$absoluteClient/$project"

  # Loop through the array using a for loop
  for ENV in "${MY_ENV[@]}"; do
    if [ ! -z "$(docker images -q project_${CLIENT}_${PROJECT}_${DOCKER_ENV} 2> /dev/null)" ]; then
      docker image rm "project_${CLIENT}_${PROJECT}_${DOCKER_ENV}"
      display_message --text "Docker image has been deleted"
    fi
  done

  rm -R "$absoluteClient/$project"
  display_message --text "Your project $absoluteClient/$project has been deleted"

done

if [[ "$tmpProjects" == "all" ]];
then
  display_message --text "Your client $absoluteClient has been deleted"
  rm -R "$absoluteClient"
fi