ARG PHP_VERSION="8.4"

FROM php:${PHP_VERSION}-alpine

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs --no-dev &&\
  composer install --prefer-dist --no-dev --no-interaction --no-scripts &&\
  composer dumpautoload -o
