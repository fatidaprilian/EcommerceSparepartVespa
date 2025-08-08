FROM dunglas/frankenphp:php8.3-alpine

WORKDIR /app

# Install system dependencies
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
    make

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
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

# Copy and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --no-scripts --ignore-platform-reqs

# Copy application
COPY . .
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Set permissions
RUN chmod -R 777 /app/storage /app/bootstrap/cache

# Create simple health check that doesn't depend on database
RUN echo '<?php http_response_code(200); echo "OK"; ?>' > /app/public/health.php

# Laravel setup with error handling (skip if database not ready)
RUN php artisan key:generate --force || echo "Key generation skipped"
RUN php artisan config:cache || echo "Config cache skipped"
RUN php artisan route:cache || echo "Route cache skipped"
RUN php artisan view:cache || echo "View cache skipped"

# Create startup script to handle database migrations
RUN cat > /app/startup.sh << 'EOF'
#!/bin/sh

echo "Starting Laravel application..."

# Wait for database to be ready (optional)
echo "Checking database connection..."
php artisan migrate --force || echo "Migration failed, continuing anyway..."

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting FrankenPHP..."
exec frankenphp run --config /etc/caddy/Caddyfile
EOF

RUN chmod +x /app/startup.sh

# Create Caddyfile with better error handling
RUN cat > /etc/caddy/Caddyfile << 'EOF'
:8080 {
	root * /app/public
	php_server
	
	# Health check endpoint
	handle /health {
		respond "OK" 200
	}
	
	# Handle Laravel index
	handle {
		try_files {path} {path}/ /index.php?{query}
	}
	
	# Logging
	log {
		output stdout
		format console
	}
}
EOF

EXPOSE 8080

# Use startup script instead of direct frankenphp
CMD ["/app/startup.sh"]