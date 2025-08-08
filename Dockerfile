# --- Tahap 1: Build Stage (PHP + Node) ---
FROM composer:2.7 as build
WORKDIR /app

# [FIX v3] Memisahkan instalasi runtime deps dan build deps.
# 1. Install dependensi yang dibutuhkan saat runtime (tidak akan dihapus).
RUN apk add --no-cache icu-libs libzip nodejs npm

# 2. Install dependensi untuk build sebagai paket virtual SEMENTARA.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev \
    && docker-php-ext-install zip intl \
    && apk del .build-deps # 3. Hapus HANYA dependensi build setelah selesai.

# Salin semua file project
COPY . .

# Install dependensi Composer
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Install dependensi NPM dan build aset frontend
RUN npm ci && npm run build


# --- Tahap 2: Production Image ---
FROM dunglas/frankenphp:php8.3-alpine as production
WORKDIR /app

# Salin runtime dependencies dari tahap build
COPY --from=build /usr/lib/libicu* /usr/lib/

# Salin semua file yang sudah ter-build
COPY --chown=frankenphp:frankenphp --from=build /app .

# Berikan izin yang benar dan aman untuk Laravel
RUN chown -R frankenphp:frankenphp /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Jalankan optimasi Laravel untuk production
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN php artisan event:cache

# Expose port yang digunakan oleh Caddy (web server FrankenPHP)
EXPOSE 80 443 443/udp