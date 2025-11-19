FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install UOPZ and simdjson extensions via PECL
RUN pecl install uopz simdjson \
    && docker-php-ext-enable uopz simdjson

# Verify extensions are loaded
RUN php -m | grep -E "(simdjson|uopz)" && echo "✓ Extensions loaded successfully"

WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-interaction --no-scripts --prefer-dist || true

# Copy application code
COPY . .

# Run composer install again with scripts
RUN composer install --no-interaction --prefer-dist

CMD ["php", "--version"]
