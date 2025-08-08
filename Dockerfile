# --- Tahap 1: Backend Dependencies ---
# Menggunakan image composer resmi untuk menginstall dependensi PHP.
# Ini memanfaatkan cache Docker secara efisien.
FROM composer:2.7 as vendor
WORKDIR /app
COPY database/ database/
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist

# --- Tahap 2: Frontend Dependencies ---
# Menggunakan image Node.js untuk membangun aset frontend.
# Tahap ini akan dibuang dan tidak akan ada di image final.
FROM node:20-alpine as frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
RUN npm run build

# --- Tahap 3: Production Image ---
# Memulai dari image FrankenPHP yang ramping.
FROM dunglas/frankenphp:php8.3-alpine as production

# Install dependensi yang diperlukan untuk Laravel
RUN apk add --no-cache \
    php83-pdo \
    php83-pdo_mysql \
    php83-mbstring \
    php83-xml \
    php83-curl \
    php83-zip \
    php83-bcmath \
    php83-gd \
    php83-tokenizer \
    php83-fileinfo \
    php83-opcache

WORKDIR /app

# [PENTING] User default di image ini adalah 'frankenphp'.
# Kita akan menyalin file dengan user ini untuk izin yang benar.
COPY --chown=frankenphp:frankenphp . .

# Salin artifak dari tahap-tahap sebelumnya
COPY --from=vendor --chown=frankenphp:frankenphp /app/vendor/ ./vendor/
COPY --from=frontend --chown=frankenphp:frankenphp /app/public/build ./public/build

# Berikan izin yang benar dan aman untuk Laravel
RUN chown -R frankenphp:frankenphp /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Install composer untuk menjalankan optimasi
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Jalankan optimasi Laravel untuk production
# Ini membuat aplikasi Anda lebih cepat.
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

# Generate application key jika belum ada
RUN php artisan key:generate --no-interaction --force || true

# Laravel optimizations
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache

# Railway specific: Expose port yang digunakan Railway
EXPOSE 8080

# Jalankan FrankenPHP dengan port Railway
CMD ["frankenphp", "run", "--listen", ":8080"]