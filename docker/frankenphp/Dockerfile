# --- Tahap 1: Dapatkan Composer ---
FROM composer:lts as composer

# --- Tahap 2: Bangun Image Aplikasi Utama ---
FROM dunglas/frankenphp:php8.4-alpine

# Atur direktori kerja
WORKDIR /app

# Install dependensi sistem yang dibutuhkan oleh Laravel & ekstensi PHP umum
RUN apk add --no-cache \
    libzip-dev \
    icu-dev \
    nodejs \
    npm \
    && docker-php-ext-install \
    zip \
    intl \
    pdo_mysql

# Salin binary Composer dari tahap pertama
COPY --from=composer /usr/bin/composer /usr/bin/composer

# --- PERUBAHAN DI SINI ---
# Salin Caddyfile yang sudah benar ke dalam image.
# Caddy akan otomatis memuatnya saat dijalankan.
COPY Caddyfile /etc/caddy/Caddyfile

# Salin file definisi dependensi
COPY composer.json composer.lock ./

# Install dependensi PHP untuk production
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Salin sisa kode aplikasi ke dalam image
COPY . .

# Jalankan kembali composer install untuk menjalankan skrip
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Berikan izin tulis ke direktori yang dibutuhkan Laravel
RUN chmod -R 777 /app/storage /app/bootstrap/cache

# Expose port untuk HTTP, HTTPS, dan HTTP/3
EXPOSE 80 443 443/udp