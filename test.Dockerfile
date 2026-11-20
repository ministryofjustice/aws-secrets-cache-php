ARG PHP_VERSION="8.4"
ARG PHP_SHA="af068e577aaaf473344a53254078f6daa8e3d482cd6d6050e6da5786763cc5b4"

FROM php:${PHP_VERSION}-alpine@sha256:${PHP_SHA}

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs &&\
  composer install --prefer-dist --no-interaction --no-scripts &&\
  composer dumpautoload -o
