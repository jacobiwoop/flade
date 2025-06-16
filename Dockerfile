FROM php:8.1-apache

# Installer pdo_mysql
RUN docker-php-ext-install pdo_mysql

# Installer les dépendances système pour composer et zip (exemple)
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Installer composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist

RUN a2enmod rewrite

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80 8080

CMD ["/start.sh"]
