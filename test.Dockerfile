ARG PHP_VERSION="8.4"
ARG PHP_SHA="17bbc8e655709d42c56e4eb24a9c715105a7e446358dba4d26dc06e514d520ef"

FROM php:${PHP_VERSION}-alpine@sha256:${PHP_SHA}

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs &&\
  composer install --prefer-dist --no-interaction --no-scripts &&\
  composer dumpautoload -o
