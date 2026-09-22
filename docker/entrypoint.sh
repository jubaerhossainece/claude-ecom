#!/bin/sh
set -e

if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    until php -r "new PDO('mysql:host=$DB_HOST;port=${DB_PORT:-3306}', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
        sleep 1
    done
    echo "Database is up."
fi

# Skipped when bind-mounting the repo for local dev (docker-compose.override.yml
# sets this) — caching a view/route/config on boot would keep serving the
# cached copy even after you edit a Blade/PHP file on the host, defeating the
# whole point of the bind mount.
if [ "$SKIP_BOOT_CACHE" != "true" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Deliberately NOT running migrate:fresh here, ever — see CLAUDE.md for why.
# Set RUN_MIGRATIONS=true to apply pending migrations (non-destructive) on boot.
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
