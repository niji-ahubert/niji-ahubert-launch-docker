#!/bin/bash
set -e

dirname="/app"
cd "$dirname"

if [ -f package.json ]; then
    pnpm install
fi

# Exécuter le script entrypoint-addon.sh s'il existe
if [ -f "$dirname/bin/entrypoint-addon.sh" ]; then
  echo "🔧 Exécution de l'entrypoint additionnel..."
  source "$dirname/bin/entrypoint-addon.sh"
fi

exec "$@"
