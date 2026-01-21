FROM php:8.0-apache

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install --no-dev --optimize-autoloader

COPY . .

# Fix SDK bug: remove non-existent PHP constants
RUN sed -i 's/filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED)/filter_var($url, FILTER_VALIDATE_URL)/' \
    /var/www/html/vendor/rocket-labs/sellercenter-sdk-php/src/RocketLabs/SellerCenterSdk/Core/Configuration.php

RUN composer dump-autoload --optimize

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Declarar variables de entorno para que Coolify las detecte
ENV FALABELLA_ENDPOINT=""
ENV FALABELLA_USER_ID=""
ENV FALABELLA_API_KEY=""

EXPOSE 80

CMD ["apache2-foreground"]