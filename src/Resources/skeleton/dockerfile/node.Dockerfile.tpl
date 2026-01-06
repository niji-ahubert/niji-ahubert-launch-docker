<?= $fromStatement ?>

# Activation de corepack pour pnpm
ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable

WORKDIR /var/www/html

# Copie le reste du code source
COPY . .

# Expose le port configuré pour ce service
EXPOSE <?= $port ?>

# Commande par défaut pour démarrer l'application
CMD ["pnpm", "start"]

FROM stage_dev AS stage_prod
COPY . /var/www/html
RUN pnpm install --prod --frozen-lockfile