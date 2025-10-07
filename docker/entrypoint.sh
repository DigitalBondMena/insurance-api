#!/bin/sh

echo "Starting application setup..."

# Wait for database to be ready (using simple connection test)
MAX_TRIES=30
COUNT=0
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null || [ $COUNT -eq $MAX_TRIES ]; do
    COUNT=$((COUNT+1))
    echo "Waiting for database connection... (${COUNT}/${MAX_TRIES})"
    sleep 3
done

if [ $COUNT -eq $MAX_TRIES ]; then
    echo "WARNING: Could not connect to database after ${MAX_TRIES} attempts. Continuing anyway..."
else
    echo "Database connection successful!"
    
    # Run migrations
    php artisan migrate --force --no-interaction || echo "Migration failed or already done"
fi

# Clear caches first
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Don't cache routes/config on startup (will be done manually after deployment)
echo "Caches cleared. Ready to start!"

# Create storage link if not exists
php artisan storage:link || true

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage || true
chown -R www-data:www-data /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage || true
chmod -R 775 /var/www/html/bootstrap/cache || true

echo "Application setup completed!"

# Execute the main command
exec "$@"

