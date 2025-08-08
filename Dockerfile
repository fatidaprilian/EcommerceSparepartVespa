# --- Tahap 1: Backend Dependencies ---
FROM composer:2.7 as vendor
WORKDIR /app
COPY database/ database/
COPY composer.json composer.lock ./
# Install dengan ignore platform requirements untuk tahap ini
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist --ignore-platform-reqs

# --- Tahap 2: Frontend Dependencies ---
FROM node:20-alpine as frontend
WORKDIR /app
COPY package*.json ./
# Install ALL dependencies (termasuk dev) untuk build
RUN npm ci
COPY . .
RUN npm run build

# --- Tahap 3: Production Image ---
FROM dunglas/frankenphp:php8.3-alpine as production

# Install dependensi yang diperlukan untuk Laravel
RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    curl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    xml \
    curl \
    zip \
    bcmath \
    gd \
    tokenizer \
    fileinfo \
    opcache \
    intl

WORKDIR /app

# Salin aplikasi dengan ownership yang benar
COPY --chown=frankenphp:frankenphp . .

# Salin artifak dari tahap-tahap sebelumnya
COPY --from=vendor --chown=frankenphp:frankenphp /app/vendor/ ./vendor/
COPY --from=frontend --chown=frankenphp:frankenphp /app/public/build ./public/build

# Berikan izin yang benar untuk Laravel
RUN chown -R frankenphp:frankenphp /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Install composer untuk optimasi
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Install dependencies lagi dengan platform requirements
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

# Generate application key jika belum ada
RUN php artisan key:generate --no-interaction --force || true

# Laravel optimizations
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache

# Expose port untuk Easypanel
EXPOSE 8080

# Jalankan FrankenPHP
CMD ["frankenphp", "run", "--listen", ":8080"]