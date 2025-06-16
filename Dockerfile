# Utilise une image officielle PHP avec Apache
FROM php:8.2-apache

# Installer zip si ton projet en a besoin (par exemple pour composer)
RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install zip

# Activer mod_rewrite pour les URLs propres
RUN a2enmod rewrite

# Copier les fichiers du projet dans le conteneur
COPY . /var/www/html/

# Ajouter Composer (dernière version)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Aller dans le dossier du projet
WORKDIR /var/www/html

# Installer les dépendances PHP (si composer.json existe)
RUN [ -f composer.json ] && composer install --no-interaction --prefer-dist || true

# Définir les droits (facultatif si pas de problème de permission)
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80
EXPOSE 80

# Démarrer Apache
CMD ["apache2-foreground"]
