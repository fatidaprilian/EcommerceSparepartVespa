# --- Tahap 1: Backend Dependencies ---
# Menggunakan image composer resmi untuk menginstall dependensi PHP.
FROM composer:2.7 as vendor
WORKDIR /app

# [FIX] Install dependensi sistem dan ekstensi PHP yang dibutuhkan oleh project.
RUN apk add --no-cache libzip-dev icu-dev \
    && docker-php-ext-install zip intl

COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist


# --- Tahap 2: Frontend Dependencies ---
# Menggunakan image Node.js untuk membangun aset frontend.
FROM node:20-alpine as frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build


# --- Tahap 3: Production Image ---
# Memulai dari image FrankenPHP yang ramping.
FROM dunglas/frankenphp:php8.3-alpine as production
WORKDIR /app

# [PENTING] User default di image ini adalah 'frankenphp'.
COPY --chown=frankenphp:frankenphp . .

# Salin artifak dari tahap-tahap sebelumnya
COPY --from=vendor /app/vendor/ ./vendor/
COPY --from=frontend /app/public/build ./public/build

# Berikan izin yang benar dan aman untuk Laravel
RUN chown -R frankenphp:frankenphp /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Jalankan optimasi Laravel untuk production
RUN composer install --no-interaction --no-dev --optimize-autoloader
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN php artisan event:cache

# Expose port yang digunakan oleh Caddy (web server FrankenPHP)
EXPOSE 80 443 443/udp