# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.3
ARG COMPOSER_VERSION=lts

FROM docker:latest AS docker_cli

FROM composer:${COMPOSER_VERSION} AS composer

FROM mlocati/php-extension-installer:latest AS php_extension_installer

FROM php:${PHP_VERSION}-fpm AS app_php_base

ENV PHP_VERSION $PHP_VERSION
ENV COMPOSER_VERSION $COMPOSER_VERSION
ENV DOCKER_ENV "prod"
ENV SYMFONY_LOCAL_SERVER false

WORKDIR /var/www/html

# php extensions installer: https://github.com/mlocati/docker-php-extension-installer
COPY --from=php_extension_installer /usr/bin/install-php-extensions /usr/local/bin/

# Récupération des arguments avec des valeurs par défaut
ARG HOST_UID=1000
ARG HOST_GID=1000

# Création du groupe et de l'utilisateur avec les UID/GID de l'hôte
RUN set -x ; \
    groupadd -g ${HOST_GID} admin ; \
    useradd -u ${HOST_UID} -g admin -G www-data -m admin;

## PACKAGE DEBIAN
RUN --mount=type=cache,target=/var/lib/apt/lists \
    --mount=type=cache,target=/var/cache/apt/archives \
    set -eux; \
    apt-get update && apt-get install -y \
    bash \
    git \
    autoconf \
    pkg-config \
    build-essential \
    gcc \
    unzip;
## END PACKAGE DEBIAN

## INSTALL COMMON PACKAGE
RUN install-php-extensions opcache
## END INSTALL COMMON PACKAGE

## PHP
RUN set -eux; \
    mkdir -p /var/run/php; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini";
## END PHP

EXPOSE 80

## COMPOSER
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV PATH /composer/vendor/bin:$PATH
COPY --from=composer /usr/bin/composer /usr/bin/composer
## END COMPOSER


## set recommended PHP.ini settings
## see https://secure.php.net/manual/en/opcache.installation.php
COPY resources/docker/php/opcache-recommended.ini /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY resources/docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
### END OPCACHE conf

#Add user & composer folder
RUN set -x ; \
    mkdir -p /composer; \
    mkdir -p /composer-cache; \
    chown -R ${HOST_UID}:www-data /composer; \
    chown -R ${HOST_UID}:www-data /composer-cache;

## COMPOSER
ENV COMPOSER_HOME /composer
ENV COMPOSER_CACHE_DIR /composer-cache
## END COMPOSER

COPY resources/docker/entrypoint/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint



## ClEAN
RUN rm -rf /tmp/* /var/cache/apk/* /var/tmp/*
LABEL authors="niji-dsf"


### PROD LAYER ###
FROM app_php_base AS app_php_prod

ARG PROJECT
ARG CLIENT
COPY projects/$CLIENT/$PROJECT/application /var/www/html


RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

ENTRYPOINT ["docker-entrypoint"]

### DEV LAYER ###
FROM app_php_base AS app_php_dev_xdebug


ENV DOCKER_ENV "dev"
ENV SYMFONY_LOCAL_SERVER true
ENV XDEBUG_TRIGGER IDE_TRIGGER_VALUE
ENV INSTALL_QUALITY_TOOLS true


## ADD SSH
RUN --mount=type=cache,target=/var/lib/apt/lists \
    --mount=type=cache,target=/var/cache/apt/archives \
    apt-get update && apt-get install -y openssh-client
## END ADD SSH

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

## XDEBUG
RUN --mount=type=cache,target=/var/lib/apt/lists \
    --mount=type=cache,target=/var/cache/apt/archives \
    apt-get update && apt-get install -y ${PHPIZE_DEPS} linux-headers-amd64 \
    && install-php-extensions xdebug
## END XDEBUG

FROM app_php_dev_xdebug AS app_php_dev

RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini

ENTRYPOINT ["docker-entrypoint"]


FROM app_php_dev_xdebug AS niji_generator_common

# Configuration des permissions
RUN mkdir -p /opt/envfile /var/www/html && \
    chown -R admin:admin /opt/envfile /var/www/html

# Configuration de l'environnement
ENV COMPOSER_HOME=/home/admin/.composer
ENV DOCKER_GID=997

WORKDIR /opt/envfile

## INSTALL COMMON PACKAGE
RUN install-php-extensions zip intl
## END INSTALL COMMON PACKAGE


# Installation de Docker CLI et Docker Compose plugin
COPY --from=docker_cli /usr/local/bin/docker /usr/local/bin/docker
RUN mkdir -p /usr/local/libexec/docker/cli-plugins
COPY --from=docker_cli /usr/local/libexec/docker/cli-plugins/docker-compose /usr/local/libexec/docker/cli-plugins/docker-compose
COPY --from=docker_cli /usr/local/libexec/docker/cli-plugins/docker-buildx /usr/local/libexec/docker/cli-plugins/docker-buildx
RUN chmod +x /usr/local/libexec/docker/cli-plugins/docker-compose /usr/local/libexec/docker/cli-plugins/docker-buildx

# Configuration des permissions Docker avec GID dynamique
# Le GID doit correspondre au groupe docker de l'hôte pour éviter les erreurs de permissions
ARG DOCKER_GID=997
RUN --mount=type=cache,target=/var/lib/apt/lists \
    --mount=type=cache,target=/var/cache/apt/archives \
    apt-get update && apt-get install -y --no-install-recommends ca-certificates \
    && groupadd -f -g ${DOCKER_GID} docker \
    && usermod -aG ${DOCKER_GID} admin \
    && usermod -aG ${DOCKER_GID} www-data



USER admin

FROM niji_generator_common AS niji_generator_client

CMD ["php", "-a"]

FROM niji_generator_common AS niji_generator_webserver

CMD ["php-fpm"]
