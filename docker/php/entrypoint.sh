#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --optimize-autoloader
fi

echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST:-host.docker.internal};port=${DB_PORT:-3306}', getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 1
done
echo "MySQL is up."

if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

exec "$@"
