#!/bin/sh
set -e

# Adapt Apache port to PaaS $PORT environment variable if defined
if [ -n "$PORT" ]; then
    sed -i "s/Listen [0-9]*/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf
fi

# Ensure storage and bootstrap directories exist with proper permissions
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Run database migrations if RUN_MIGRATIONS is set to true
if [ "$RUN_MIGRATIONS" = "true" ] || [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --isolated || true
fi

exec "$@"
