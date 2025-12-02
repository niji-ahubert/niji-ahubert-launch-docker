#!/bin/bash
source bin/library/library.sh
source bin/library/gum.sh
# Declare an array
MY_ENV=("dev" "prod")
deleteAll=false

display_message --text 'Delete project'

display_message --text 'Choose your client'
selectClient

display_message --text "Client: $client
Choose your project to Delete"
selectProject --client $client

display_message --text "Client: $client
Project: $project
Select application to Delete"
selectApplication --client $client --project $project

#absoluteClient="projects/$client/$project"

if [ -n "$choosenFolder" ]; then
    IFS=' ' read -r -a startProject <<< "$choosenFolder"
else
   retrieveApplicationsByClient --pathClientProjects "projects/$client/$project"
   deleteAll=true
fi

IFS=' ' read -r -a startProject <<< "$choosenFolder"
for projectToDelete in "${startProject[@]}"
do


     # Rechercher tous les fichiers *.env dans le projet mais pas dans les sous-dossiers
    source "$absoluteProject/config/$projectToDelete.env"
    for ENV in "${MY_ENV[@]}"; do
        if [ ! -z "$(docker images -q project-${CLIENT}-${PROJECT}-${FOLDER_NAME}-${ENV} 2> /dev/null)" ]; then
            docker image rm "project-${CLIENT}-${PROJECT}-${FOLDER_NAME}-${ENV}"
            display_message --text "Docker image project-${CLIENT}-${PROJECT}-${FOLDER_NAME}-${ENV} has been deleted"
        fi
    done

    rm -R "$absoluteProject/$projectToDelete"
    display_message --text "Your project $absoluteProject/$projectToDelete has been deleted"

done

if [ "$deleteAll" == true ]; then
    display_message --text "Your client $absoluteProject has been deleted"
    rm -R "$absoluteProject"
fi
