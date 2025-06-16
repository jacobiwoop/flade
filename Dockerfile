FROM php:8.1-apache

# Installer les dépendances nécessaires pour Ratchet
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip

# Installer composer (pour Ratchet et autoload)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier tout le code source
COPY . .

# Installer les dépendances PHP (Ratchet etc.)
RUN composer install

# Activer le module rewrite d’Apache (optionnel mais fréquent)
RUN a2enmod rewrite

# Exposer le port HTTP classique
EXPOSE 80

# Copier le script de démarrage
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Commande pour démarrer les 2 serveurs
CMD ["/start.sh"]
