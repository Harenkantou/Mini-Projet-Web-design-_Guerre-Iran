FROM php:8.2-fpm

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Installer l'extension mysqli
RUN docker-php-ext-install mysqli

# Configurer PHP
RUN echo "display_errors = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini

WORKDIR /app