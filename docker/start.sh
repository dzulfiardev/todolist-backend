#!/bin/bash

# Laravel 12 Docker startup script
set -e

echo "Starting Laravel 12 TodoList Application..."

# Install netcat for database connectivity checks
apt-get update && apt-get install -y netcat-openbsd

# Wait for database to be ready with more robust check
echo "Waiting for database..."
timeout=60
while [ $timeout -gt 0 ]; do
    if nc -z db 3306; then
        echo "Database port is open, checking if MySQL is ready..."
        if mysql -h db -u root -proot -e "SELECT 1" >/dev/null 2>&1; then
            echo "Database is ready!"
            break
        fi
    fi
    echo "Database is unavailable - sleeping (${timeout}s remaining)"
    sleep 2
    timeout=$((timeout-2))
done

if [ $timeout -le 0 ]; then
    echo "Database connection timeout!"
    exit 1
fi

# Test database connectivity
nc -z db 3306 && echo "Connection to db ($(getent hosts db | awk '{ print $1 }')) 3306 port [tcp/mysql] succeeded!"

# Wait for database to be ready
# echo "Waiting for database..."
# until nc -z db 3306; do
#   echo "Database is unavailable - sleeping"
#   sleep 2
# done
# echo "Database is up!"

# Wait for test database to be ready
# echo "Waiting for test database..."
# until nc -z db 3306; do
#   echo "Test database is unavailable - sleeping"  
#   sleep 2
# done
# echo "Test database is up!"

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www
chmod -R 755 /var/www
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Create .env if not exists
if [ ! -f /var/www/.env ]; then
    echo "Creating .env file..."
    cp /var/www/.env.example /var/www/.env
fi

# Change to app directory
cd /var/www

# Install/update dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Generate application key if not exists
echo "Generating application key..."
php artisan key:generate --force

# Clear all caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Create storage symlink
echo "Creating storage symlink..."
php artisan storage:link || true

# Run migrations for main database
echo "Running main database migrations..."
php artisan migrate --force

# Run migrations for test database
echo "Running test database migrations..."
php artisan migrate --env=testing --force || echo "Test migrations failed, continuing..."

# Cache configuration for better performance
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create log directories
mkdir -p /var/log/supervisor
mkdir -p /var/log/nginx
touch /var/log/nginx/access.log
touch /var/log/nginx/error.log
chown -R www-data:www-data /var/log/nginx

echo "Laravel 12 TodoList Application started successfully!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf