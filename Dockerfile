# --- Tahap 1: Build Stage (PHP + Node) ---
# Menggabungkan instalasi backend dan frontend dalam satu tahap
# agar semua file bisa diakses saat build.
FROM composer:2.7 as build
WORKDIR /app

# Install dependensi sistem, ekstensi PHP, DAN Node.js + npm
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev nodejs npm \
    && docker-php-ext-install zip intl \
    && apk del .build-deps

# Salin semua file project
COPY . .

# Install dependensi Composer
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Install dependensi NPM dan build aset frontend
RUN npm ci && npm run build


# --- Tahap 2: Production Image ---
# Memulai dari image FrankenPHP yang ramping untuk hasil akhir.
FROM dunglas/frankenphp:php8.3-alpine as production
WORKDIR /app

# Salin semua file yang sudah ter-build, termasuk folder vendor dan aset
COPY --chown=frankenphp:frankenphp --from=build /app .

# Berikan izin yang benar dan aman untuk Laravel
RUN chown -R frankenphp:frankenphp /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Jalankan optimasi Laravel untuk production
# (composer install tidak perlu lagi karena vendor sudah disalin)
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN php artisan event:cache

# Expose port yang digunakan oleh Caddy (web server FrankenPHP)
EXPOSE 80 443 443/udp