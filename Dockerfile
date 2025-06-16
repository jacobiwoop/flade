FROM php:8.1-apache

# Installer les dépendances système pour composer et zip (exemple)
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Installer composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le code source
WORKDIR /var/www/html
COPY . .

# Installer les dépendances PHP via composer
RUN composer install --no-interaction --prefer-dist

# Activer mod_rewrite Apache
RUN a2enmod rewrite

# Copier et rendre exécutable le script de démarrage
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Exposer ports Apache (80) et WebSocket (8081)
EXPOSE 80 8081

# Démarrer Apache et WebSocket
CMD ["/start.sh"]
