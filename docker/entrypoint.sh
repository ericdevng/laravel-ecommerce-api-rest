#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

# Solo cacheamos para el servidor web. Así comandos como
# "php artisan key:generate" o "php artisan config:clear" no se ven afectados.
case "$1" in
    apache2-foreground|php-fpm)
        php artisan config:cache
        php artisan view:cache
        php artisan storage:link >/dev/null 2>&1 || true
        ;;
esac

exec "$@"
