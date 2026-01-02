<?= $fromStatement ?>

# Activation de corepack pour pnpm
ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable

WORKDIR /app

# Copie des fichiers de définition des dépendances
COPY package.json pnpm-lock.yaml* ./

# Installation des dépendances
RUN pnpm install

# Copie le reste du code source
COPY . .

# Expose le port configuré pour ce service
EXPOSE <?= $port ?>

# Commande par défaut pour démarrer l'application
CMD ["pnpm", "start"]