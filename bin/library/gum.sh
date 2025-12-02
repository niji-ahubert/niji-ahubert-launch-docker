#!/bin/bash
source bin/library/library.sh

display_message() {
  parseArguments "$@"
  alignParam=${align:-"center"}
  gum style --foreground 212 --border-foreground 240 --border double --align "$alignParam" --width 50 --margin "1 2" --padding "2 4" "$text"
}

function selectClient(){

  parseArguments "$@"
  absoluteClient=$(gum file projects --directory --height=10)
  client=$(basename "$absoluteClient")

}

function selectProject(){

  parseArguments "$@"
  absoluteProject=$(gum file projects/$client --directory --height=10)
  project=$(basename "$absoluteProject")
}

function retrieveApplicationsByClient(){

    parseArguments "$@"
    # Trouver les dossiers au premier niveau et les stocker dans une variable avec un espace comme séparateur
    choosenFolder=$(find "$pathClientProjects" -maxdepth 1 -type d -not -path "$pathClientProjects" -not -name "config" -not -name "website" -exec basename {} \; | tr '\n' ' ')
}


function selectApplication(){

  parseArguments "$@"
  folderType=$(gum choose "All" "Custom choice")
  choosenFolder=""
  if [ "$folderType" == "Custom choice" ]; then
    # Récupère la liste des dossiers
    folders=$(find "projects/$client/$project" -maxdepth 1 -type d -not -name "config" -not -name "website" -exec basename {} \; | tr '\n' ' ')
    # Utilise gum choose pour la sélection interactive
    absoluteProjectFolder=$(gum choose $folders)
    choosenFolder=$(basename "$absoluteProjectFolder")
  fi
}

function checkContainers() {
    parseArguments "$@"
    IFS=' ' read -ra arrayFolder <<< "$dossiers"
    for projects in "${arrayFolder[@]}"; do
      if [ "$(docker ps -q -f name=container_${client}_${projects}_dev)" ]; then
        echo $projects
      fi
    done
}
