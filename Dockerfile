# ============================================================
# Stage 1: BASE — PHP 8.3 FPM Alpine with all extensions
# ============================================================
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        pcntl \
        bcmath \
        opcache \
        zip \
        exif \
        mbstring

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# ============================================================
# Stage 2: DEVELOPMENT — Xdebug + artisan serve
# ============================================================
FROM base AS development

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configure Xdebug
RUN echo "xdebug.mode=develop,debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# PHP development config
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# Configure PHP settings
RUN echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/custom.ini

# Copy application source
COPY . /var/www/html

# Install dependencies (dev included)
RUN if [ -f composer.json ]; then \
        composer install --no-interaction --prefer-dist; \
    fi

# Expose port 8000 for artisan serve
EXPOSE 8000

# Default command: artisan serve
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ============================================================
# Stage 3: BUILDER — Production dependency install + caching
# ============================================================
FROM base AS builder

COPY . /var/www/html

# Install production-only dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Cache config, routes, views
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# ============================================================
# Stage 4: PRODUCTION — Non-root PHP-FPM
# ============================================================
FROM base AS production

# PHP production config
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Configure opcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP settings for production
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/custom.ini

# Create non-root user
RUN addgroup -g 1000 -S appuser \
    && adduser -u 1000 -S appuser -G appuser

# Copy built application from builder stage
COPY --from=builder --chown=appuser:appuser /var/www/html /var/www/html

# Switch to non-root user
USER appuser

# Expose PHP-FPM port
EXPOSE 9000

# Run PHP-FPM
CMD ["php-fpm"]
