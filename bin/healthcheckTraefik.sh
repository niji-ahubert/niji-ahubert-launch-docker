#!/bin/bash


IS_RUNNING=$(docker --log-level=ERROR compose --project-name docker-traefik-portainer ps -q --status=running traefik2)
if [[ "$IS_RUNNING" == "" ]]; then
  IS_RUNNING=$(docker --log-level=ERROR compose --project-name docker-dev-host ps -q --status=running traefik)
fi

if [[ "$IS_RUNNING" != "" ]]; then
  echo '🌐 Traefik is currently running'
else
  echo '🌐 Traefik is not running please start it first! See: https://gitlab.niji.fr/dsf/docker-dev-host'
  exit 1;
fi
