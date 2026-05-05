FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
 HEAD
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

    libldap2-dev \
    ldap-utils \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install ldap

# Optionnel mais utile pour debug
RUN php -m | grep ldap || true
