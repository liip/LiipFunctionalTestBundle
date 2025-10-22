FROM php:8.2-alpine
#FROM php:7.4-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add --no-cache sqlite-dev

RUN docker-php-ext-install pdo pdo_sqlite

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer
