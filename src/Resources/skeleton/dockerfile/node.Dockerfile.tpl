<?= $fromStatement ?>

# Copie le code source spécifique du projet
COPY . .

# Expose le port configuré pour ce service
EXPOSE <?= $port ?>

# Commande par défaut pour démarrer l'application
CMD ["npm", "start"]

