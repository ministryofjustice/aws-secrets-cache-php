ARG PHP_VERSION="8.4"
ARG PHP_SHA="9b82b39a26336318b0d3b0d12c6e21a292115c4223ebece6ce9b3aa8c9f86bd3"

FROM php:${PHP_VERSION}-alpine@sha256:${PHP_SHA}

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs &&\
  composer install --prefer-dist --no-interaction --no-scripts &&\
  composer dumpautoload -o
