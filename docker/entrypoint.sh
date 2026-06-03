#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

wait_for() {
    local host="$1" port="$2" name="$3"
    echo "[entrypoint] Waiting for ${name} at ${host}:${port}..."
    for i in $(seq 1 60); do
        if php -r "exit(@fsockopen('${host}', ${port}) ? 0 : 1);"; then
            echo "[entrypoint] ${name} is up."
            return 0
        fi
        sleep 2
    done
    echo "[entrypoint] ERROR: ${name} not reachable."
    exit 1
}

if [ ! -f .env ]; then
    cp .env.example .env
    echo "[entrypoint] Created .env from .env.example"
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null && ! grep -q '^APP_KEY=.' .env | grep -v '^APP_KEY=$'; then
    php artisan key:generate --force --no-interaction || true
fi

if [ -f vendor/autoload.php ]; then
    :
elif [ -f composer.json ]; then
    composer install --no-interaction --prefer-dist
fi

wait_for "${DB_HOST:-mysql}" "${DB_PORT:-3306}" "MySQL"
wait_for "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}" "Redis"

php artisan migrate --force --no-interaction

if ! php artisan tinker --execute="echo \\App\\Models\\User::hasAdmin() ? 'yes' : 'no';" 2>/dev/null | grep -q yes; then
    echo "[entrypoint] No admin yet. Run: docker compose -f docker/docker-compose.yml exec app php artisan app:init-admin"
    echo "[entrypoint] Or open http://localhost:8080/setup"
fi

php artisan storage:link --force 2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
