#!/usr/bin/env bash
# docker/vps/entrypoint.sh — startup for the VPS production image.
# Role is selected by the container command: serve (default), worker, scheduler, reverb.
# serve runs migrations/storage setup once; worker, scheduler, and reverb wait for it via
# the "app" service healthcheck in docker-compose.vps.yml, so they never race it.
set -euo pipefail

cd /var/www/html
ROLE="${1:-serve}"

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
    echo "[entrypoint] ERROR: ${name} not reachable after 120s. Aborting."
    exit 1
}

case "$ROLE" in
serve|worker|scheduler|reverb)
    wait_for "${DB_HOST:-mysql}" "${DB_PORT:-3306}" "MySQL"
    wait_for "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}" "Redis"

    if [[ -z "${APP_KEY:-}" ]]; then
        echo "[entrypoint] ERROR: APP_KEY is not set."
        echo "  Generate one and put it in your .env file before starting:"
        echo "  docker compose -f docker-compose.vps.yml run --rm app php artisan key:generate --show"
        exit 1
    fi
    ;;
esac

case "$ROLE" in
serve)
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

    php artisan storage:link --force 2>/dev/null || true
    # Clear (not cache) compiled config/routes/views: some routes use closures,
    # which `route:cache` cannot serialize, and env vars can change between deploys.
    php artisan optimize:clear --no-interaction
    php artisan migrate --force --no-interaction

    if ! php artisan tinker --execute="echo \\App\\Models\\User::hasAdmin() ? 'yes' : 'no';" 2>/dev/null | grep -q yes; then
        echo "[entrypoint] No admin yet. Visit /setup to configure the store and administrator."
    fi

    if [ ! -f public/build/manifest.json ]; then
        echo "[entrypoint] ERROR: public/build/manifest.json missing — Vite assets were not built into the image."
        exit 1
    fi

    cd public
    exec php -S "0.0.0.0:${PORT:-8080}" server.php
    ;;
worker)
    exec php artisan queue:work redis --sleep=1 --tries=3 --timeout=120
    ;;
scheduler)
    exec php artisan schedule:work
    ;;
reverb)
    exec php artisan reverb:start
    ;;
*)
    exec "$@"
    ;;
esac
