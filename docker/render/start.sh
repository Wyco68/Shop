#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Normalize APP_KEY for Laravel (Render often omits the base64: prefix or adds quotes)
APP_KEY="${APP_KEY:-}"
APP_KEY="${APP_KEY#\"}" ; APP_KEY="${APP_KEY%\"}"
APP_KEY="${APP_KEY#\'}" ; APP_KEY="${APP_KEY%\'}"

if [[ -z "$APP_KEY" ]]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    echo "[start] APP_KEY missing — generated a runtime key."
elif [[ "$APP_KEY" != base64:* ]]; then
    if [[ "$APP_KEY" =~ ^[A-Za-z0-9+/]+=*$ ]]; then
        export APP_KEY="base64:${APP_KEY}"
        echo "[start] APP_KEY normalized to base64:… format."
    else
        export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
        echo "[start] APP_KEY invalid — generated a runtime key."
    fi
else
    export APP_KEY="$APP_KEY"
fi

# Writable paths (Render container)
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# SQLite demo database file (honour absolute DB_DATABASE from Render env)
if [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    if [ ! -f "$DB_FILE" ]; then
        touch "$DB_FILE"
    fi
    chmod 664 "$DB_FILE" 2>/dev/null || true
fi

php artisan storage:link --force 2>/dev/null || true

# Avoid stale cached config from a previous deploy (common 500 cause when APP_URL was unset)
php artisan optimize:clear --no-interaction 2>/dev/null || true

php artisan migrate --force --no-interaction

if [ "${APP_DEMO_MODE:-false}" = "true" ]; then
    php artisan db:seed --class=RenderDemoSeeder --force --no-interaction
fi

if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="${RENDER_EXTERNAL_URL}"
    echo "[start] APP_URL set from RENDER_EXTERNAL_URL=${APP_URL}"
fi

if [ -z "${APP_URL:-}" ]; then
    echo "[start] WARNING: APP_URL is not set — set https://your-app.onrender.com in Render Environment."
fi

export RENDER=true

if [ ! -f public/build/manifest.json ]; then
    echo "[start] ERROR: public/build/manifest.json missing — Vite assets were not built into the Docker image."
    exit 1
fi

# public/server.php serves static /build/* and trusts X-Forwarded-Proto before Laravel boots
cd public
exec php -S "0.0.0.0:${PORT:-10000}" server.php
