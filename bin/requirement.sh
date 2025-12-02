#!/bin/bash

if ! command -v gum &> /dev/null
then
    echo "gum must be install please launch make setup";
    exit 1
fi


if ! command -v jq &> /dev/null
then
    echo "jq n'est pas installé. Vous pouvez l'installer via apt-get install jq (sur Ubuntu/Debian) ou brew install jq (sur macOS)."
    exit 1
fi