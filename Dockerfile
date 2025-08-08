FROM dunglas/frankenphp:php8.3-alpine

WORKDIR /app

# Install dependencies + curl untuk health check
RUN apk add --no-cache \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    curl-dev \
    curl \
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
RUN composer install --no-interaction --no-dev --no-scripts --ignore-platform-reqs

# Copy aplikasi
COPY . .
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Permissions
RUN chmod -R 777 /app/storage /app/bootstrap/cache

# Laravel optimizations
RUN php artisan key:generate --force || true
RUN php artisan config:cache || true
RUN php artisan route:cache || true  
RUN php artisan view:cache || true
RUN php artisan --version

# Create simple health check endpoint
RUN echo '<?php echo "OK"; ?>' > /app/public/health.php

# Create Caddyfile dengan health check route
RUN cat > /etc/caddy/Caddyfile << 'EOF'
:8080 {
root * /app/public
encode gzip
php_server

# Health check endpoint
handle /health {
respond "OK" 200
}
}
EOF

EXPOSE 8080

ENV SERVER_NAME=":8080"

# Health check yang lebih sederhana
HEALTHCHECK --interval=30s --timeout=10s --start-period=90s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]