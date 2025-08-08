# --- Tahap 1: Dapatkan Composer ---
FROM composer:lts as composer

# --- Tahap 2: Bangun Image Aplikasi Utama ---
FROM dunglas/frankenphp:php8.4-alpine

# Atur direktori kerja
WORKDIR /app

# Install dependensi sistem, ekstensi PHP, DAN Node.js + npm
RUN apk add --no-cache \
    libzip-dev \
    icu-dev \
    nodejs \
    npm \
    && docker-php-ext-install \
    zip \
    intl \
    pdo_mysql

# Salin binary Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Salin file composer
COPY composer.json composer.lock ./

# Install dependensi PHP tanpa skrip
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Salin sisa kode aplikasi
COPY . .

# Jalankan kembali composer install untuk menjalankan skrip
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Berikan izin tulis universal ke direktori storage dan cache
RUN chmod -R 777 /app/storage /app/bootstrap/cache

# Expose port
EXPOSE 80 443 443/udp

# [PENTING] Kita tidak lagi mendefinisikan Caddyfile, CMD, atau ENTRYPOINT.
# Biarkan entrypoint default dari image dunglas/frankenphp mengambil alih.
# Ia akan secara otomatis mendeteksi Laravel dan menjalankannya.