FROM php:8.1-apache

# Installer pdo_mysql
RUN docker-php-ext-install pdo_mysql

# Installer dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    wget \
    && rm -rf /var/lib/apt/lists/*

# Installer composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Télécharger et installer ngrok
# Télécharger et installer ngrok v3
RUN wget -q https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-amd64.tgz \
    && tar -xzf ngrok-v3-stable-linux-amd64.tgz \
    && mv ngrok /usr/local/bin/ngrok \
    && rm ngrok-v3-stable-linux-amd64.tgz


# Copier le code source dans le container
WORKDIR /var/www/html
COPY . .

# Installer les dépendances PHP via composer
RUN composer install --no-interaction --prefer-dist

# Activer mod_rewrite Apache
RUN a2enmod rewrite

# Eviter le warning Apache sur ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copier le script de démarrage
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Exposer les ports Apache et WebSocket
EXPOSE 80 8081

# Commande de démarrage
CMD ["/start.sh"]
