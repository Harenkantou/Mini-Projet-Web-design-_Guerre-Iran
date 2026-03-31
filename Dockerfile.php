FROM php:8.2-apache

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Installer l'extension mysqli
RUN docker-php-ext-install mysqli

# Activer la réécriture d'URL et autoriser .htaccess
RUN a2enmod rewrite
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Configurer PHP
RUN echo "display_errors = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini

WORKDIR /var/www/html