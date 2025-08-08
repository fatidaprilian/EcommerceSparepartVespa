# --- Tahap 1: Build Stage (PHP + Node) ---
FROM composer:2.7 as build
WORKDIR /app

# Install dependensi yang dibutuhkan untuk runtime dan build
RUN apk add --no-cache icu-libs libzip nodejs npm \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev \
    && docker-php-ext-install zip intl \
    && apk del .build-deps

COPY . .

# Install dependensi Composer & NPM, lalu build aset
RUN composer install --no-interaction --no-dev --optimize-autoloader
RUN npm ci && npm run build


# --- Tahap 2: Production Image ---
FROM dunglas/frankenphp:php8.3-alpine as production
WORKDIR /app

# Salin runtime dependencies dari tahap build
COPY --from=build /usr/lib/libicu* /usr/lib/

# [FIX v4] Salin file dengan user 'www-data' yang benar
COPY --chown=www-data:www-data --from=build /app .

# [FIX v4] Berikan izin ke user 'www-data'
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Jalankan optimasi Laravel untuk production
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN php artisan event:cache

# Expose port yang digunakan oleh Caddy (web server FrankenPHP)
EXPOSE 80 443 443/udp