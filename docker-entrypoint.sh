#!/bin/sh
set -e

# Adapt Apache port to PaaS $PORT environment variable if defined
if [ -n "$PORT" ]; then
    sed -i "s/Listen [0-9]*/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf
fi

# Set proper ownership and permissions for Apache web server
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} +
find /var/www/html -type f -exec chmod 644 {} +
chmod +x /var/www/html/artisan

# Ensure storage and bootstrap directories exist with write permissions
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Run database migrations if RUN_MIGRATIONS is set to true
if [ "$RUN_MIGRATIONS" = "true" ] || [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction || true
    echo "Running database seeders..."
    php artisan db:seed --force --no-interaction || true
fi

exec "$@"
