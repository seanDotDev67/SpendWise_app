#!/bin/sh
set -e

# Cache config/routes/views at container start, when Render's env vars are available.
php artisan config:cache || echo "config:cache failed; continuing uncached"
php artisan route:cache || echo "route:cache failed; continuing uncached"
php artisan view:cache || echo "view:cache failed; continuing uncached"
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
