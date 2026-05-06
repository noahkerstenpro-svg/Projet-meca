FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libldap2-dev \
    ldap-utils \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install pdo pdo_mysql ldap zip

# Vérification au build
RUN php -m | grep -E "pdo_mysql|ldap" || true
