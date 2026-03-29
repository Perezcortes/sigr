# syntax=docker/dockerfile:1
# Laravel 12 + Filament — PHP 8.3 fijo (compatible con openspout / Excel)

FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY . .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM php:8.3-cli-bookworm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libpng-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 8000

# APP_KEY y DB vienen del entorno en Dokploy. Migraciones: comando post-deploy recomendado.
CMD php artisan storage:link 2>/dev/null || true \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan serve --host=0.0.0.0 --port=8000
