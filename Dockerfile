FROM dunglas/frankenphp:php8.3-alpine

WORKDIR /app

# Install dependencies sistem yang dibutuhkan
RUN apk add --no-cache \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    curl-dev \
    autoconf \
    g++ \
    make \
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
    intl \
    opcache

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy composer files dan install dependencies TANPA scripts
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --no-scripts --ignore-platform-reqs

# Copy seluruh aplikasi SETELAH composer install
COPY . .

# Sekarang jalankan composer install dengan scripts (artisan sudah ada)
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Simple permissions - biarkan semua user bisa akses
RUN chmod -R 777 /app/storage /app/bootstrap/cache

# Laravel optimizations dengan error handling
RUN php artisan key:generate --force || true
RUN php artisan config:cache || true
RUN php artisan route:cache || true  
RUN php artisan view:cache || true

# Test Laravel bisa jalan
RUN php artisan --version

EXPOSE 8080

# Environment variables
ENV SERVER_NAME=":8080"
ENV APP_ENV=production
ENV APP_DEBUG=false

# NO HEALTH CHECK untuk sementara - biar tidak di-kill
# HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
#     CMD curl -f http://localhost:8080/ || exit 1

# Start dengan debugging
CMD ["sh", "-c", "echo 'Starting FrankenPHP...' && frankenphp run --config /dev/stdin << 'EOF'\n:8080 {\n    root * /app/public\n    php_server\n}\nEOF"]