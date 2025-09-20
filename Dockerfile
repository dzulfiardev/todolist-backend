# Use PHP 8.2 FPM base image
FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies required for Laravel 12
# RUN apt-get update && apt-get install -y \
    # git \
    # curl \
    # libpng-dev \
    # libonig-dev \
    # libxml2-dev \
    # libfreetype6-dev \
    # libjpeg62-turbo-dev \
    # libwebp-dev \
    # libxpm-dev \
    # zip \
    # unzip \
    # libzip-dev \
    # default-mysql-client \
    # nginx \
    # supervisor \
    # redis-tools \
    # vim \
    # htop \
    # procps \
    # && apt-get clean \
    # && rm -rf /var/lib/apt/lists/*

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    nginx \
    supervisor \
    libicu-dev

# Install PHP extensions one by one to isolate issues
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mbstring  
RUN docker-php-ext-install exif
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install zip
RUN docker-php-ext-install intl
RUN docker-php-ext-install opcache
RUN docker-php-ext-install sockets    

# Configure and install PHP extensions optimized for Laravel 12
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp

RUN docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        sockets

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install and configure OPcache for better performance
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Install latest Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www

# Change current user to www
# USER www-data
USER root

COPY --chown=www-data:www-data composer.json composer.lock ./

# Install PHP dependencies optimized for Laravel 12
# RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize

# Change back to root for nginx setup
USER root

# Create docker configuration directories if they don't exist
RUN mkdir -p docker/nginx docker/supervisor docker/php docker/mysql

# Copy configurations
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create necessary directories and set proper permissions for Laravel 12
RUN mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/bootstrap/cache \
    && mkdir -p /var/www/storage/framework/{cache,sessions,views} \
    && mkdir -p /var/www/storage/app/public \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Copy startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Create log directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/log/nginx \
    && chown -R www-data:www-data /var/log/nginx

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start supervisor
CMD ["/start.sh"]