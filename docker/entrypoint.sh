#!/bin/sh

set -e

echo "Starting application setup..."

# Wait for database to be ready
until php artisan db:show 2>/dev/null; do
    echo "Waiting for database connection..."
    sleep 3
done

# Run migrations if not already run
php artisan migrate --force --no-interaction || true

# Clear and cache configuration
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if not exists
php artisan storage:link || true

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "Application setup completed!"

# Execute the main command
exec "$@"

