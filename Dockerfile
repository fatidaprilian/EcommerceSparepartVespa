# --- Tahap 1: Backend Dependencies ---
FROM composer:2.7 as vendor
WORKDIR /app

# [FIX v2] Menambahkan build-base & virtual package untuk instalasi yang lebih stabil
# dan menghapusnya setelah selesai untuk menjaga ukuran image tetap kecil.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev \
    && docker-php-ext-install zip intl \
    && apk del .build-deps

COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist


# --- Tahap 2: Frontend Dependencies ---
FROM node:20-alpine as frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build


# --- Tahap 3: Production Image ---
FROM dunglas/frankenphp:php8.3-alpine as production
WORKDIR /app

COPY --chown=frankenphp:frankenphp . .

COPY --from=vendor /app/vendor/ ./vendor/
COPY --from=frontend /app/public/build ./public/build

RUN chown -R frankenphp:frankenphp /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

RUN composer install --no-interaction --no-dev --optimize-autoloader
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN php artisan event:cache

EXPOSE 80 443 443/udp