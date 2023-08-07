FROM php:8.2-cli

WORKDIR /usr/src

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# php extensions installer: https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer:latest --link /usr/bin/install-php-extensions /usr/local/bin/

# Install dependencies
RUN apt-get update && \
    apt-get install -y \
        git \
        libzip-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* \
;

RUN set -eux; \
    install-php-extensions \
        zip \
        opcache \
        xdebug \
    ;

# PHP development config
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
ENV XDEBUG_MODE=off

# Create a defined user with UID and GID based on overridable variables (override with --build-arg)
ARG HOST_UID=1000
ARG HOST_GID=1000

RUN useradd -m dev
RUN groupmod -g ${HOST_GID} dev && \
    usermod -aG dev -u ${HOST_UID} dev

USER dev
CMD /bin/bash
