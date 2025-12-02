#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "${DIR}/.." || exit;

echo "" | XDEBUG_MODE=off docker compose exec -e XDEBUG_MODE -u <?= $uuid ?> -T -w <?= $projectDirectory ?> <?= $dockerServiceName ?> "$@"
