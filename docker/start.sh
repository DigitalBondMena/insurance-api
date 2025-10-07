#!/bin/sh

echo "Starting application..."

# Create .env from .env.example if not exists
if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
    echo "Creating .env from .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Set permissions (non-blocking)
chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true
chmod 644 /var/www/html/.env 2>/dev/null || true

# Create storage link
php artisan storage:link 2>/dev/null || true

# Clear caches
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

echo "Starting services..."

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

