# Menggunakan image yang sudah siap dengan PHP extensions
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

# Copy composer files dan install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --optimize-autoloader --ignore-platform-reqs

# Copy seluruh aplikasi
COPY . .

# Install ulang dengan platform check (sekarang extensions sudah ada)
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

# Set proper permissions untuk Laravel
RUN chown -R frankenphp:frankenphp /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Laravel optimizations dengan error handling
RUN php artisan key:generate --force || true
RUN php artisan config:cache || true
RUN php artisan route:cache || true  
RUN php artisan view:cache || true

EXPOSE 8080

CMD ["frankenphp", "run", "--listen", ":8080"]