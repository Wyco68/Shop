#!/usr/bin/env bash
# docker/entrypoint.sh — startup script for the local dev Docker image (docker/Dockerfile).
# Runs automatically on container start via docker/docker-compose.yml.
# Handles: .env creation, APP_KEY generation, composer install,
#          npm install + build, DB/Redis readiness, migrations.
set -euo pipefail

cd /var/www/html

# ── Helpers ───────────────────────────────────────────────────────────────────

wait_for() {
    local host="$1" port="$2" name="$3"
    echo "[entrypoint] Waiting for ${name} at ${host}:${port}..."
    for i in $(seq 1 60); do
        if php -r "exit(@fsockopen('${host}', ${port}) ? 0 : 1);" 2>/dev/null; then
            echo "[entrypoint] ${name} is up."
            return 0
        fi
        sleep 2
    done
    echo "[entrypoint] ERROR: ${name} not reachable after 120 s. Aborting."
    exit 1
}

# ── 1. Environment file ───────────────────────────────────────────────────────
if [ ! -f .env ]; then
    cp .env.example .env
    echo "[entrypoint] Created .env from .env.example"
fi

# ── 2. PHP vendor ─────────────────────────────────────────────────────────────
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ missing — running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# ── 3. Wait for backing services ──────────────────────────────────────────────
wait_for "${DB_HOST:-mysql}"    "${DB_PORT:-3306}"  "MySQL"
wait_for "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}" "Redis"

# ── 4. Generate APP_KEY if absent ─────────────────────────────────────────────
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction
    echo "[entrypoint] APP_KEY generated."
fi

# ── 5. Migrations ─────────────────────────────────────────────────────────────
# --except=cache: optimize:clear also runs cache:clear by default, which would
# FLUSHDB the whole Redis app-cache on every boot.
php artisan optimize:clear --except=cache --no-interaction 2>/dev/null || true
php artisan migrate --force --no-interaction

# ── 6. Storage symlink ────────────────────────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ── 7. Frontend assets (build if public/build is absent) ─────────────────────
if [ ! -f public/build/manifest.json ]; then
    echo "[entrypoint] Building frontend assets (npm ci && npm run build)..."
    npm ci --prefer-offline 2>/dev/null || npm ci
    npm run build
    echo "[entrypoint] Frontend built."
fi

# ── 8. Admin hint ─────────────────────────────────────────────────────────────
if ! php artisan tinker --execute="echo \App\Models\User::hasAdmin() ? 'yes' : 'no';" 2>/dev/null | grep -q yes; then
    echo "[entrypoint] No admin yet. Open http://localhost:8080/setup to configure the store and create the first administrator."
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
