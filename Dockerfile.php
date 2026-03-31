FROM php:8.2-apache

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    libpng-dev \
    libjpeg-dev \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN docker-php-ext-install mysqli gd

# Activer la réécriture d'URL et autoriser .htaccess
RUN a2enmod rewrite
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Configurer PHP
RUN echo "display_errors = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini
RUN echo "output_buffering = 4096" >> /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini

WORKDIR /var/www/html

# Créer les répertoires uploads
RUN mkdir -p /var/www/html/uploads/articles/editor-temp /var/www/html/uploads/articles/modif /var/www/html/images

# Copier les images depuis le répertoire host
COPY images/* /var/www/html/images/