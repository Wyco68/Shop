#!/usr/bin/env bash
# setup.sh — run once after cloning to fully bootstrap the project.
# Requirements: Git + Docker Desktop (no host PHP, Composer, or Node needed).
# Usage: bash setup.sh
set -euo pipefail

SAIL="./vendor/bin/sail"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   Car Parts E-Commerce — First-time Setup   ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# ── 1. Bootstrap PHP vendor via Docker (no local PHP required) ───────────────
if [ ! -f vendor/autoload.php ]; then
    echo "▶ [1/7] Installing PHP dependencies via Docker..."
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php84-composer:latest \
        composer install --ignore-platform-reqs --no-interaction
    echo "  ✔ PHP vendor installed."
else
    echo "  ✔ [1/7] PHP vendor already present — skipping."
fi

# ── 2. Configure environment ─────────────────────────────────────────────────
if [ ! -f .env ]; then
    echo "▶ [2/7] Creating .env from .env.example..."
    cp .env.example .env
    echo "  ✔ .env created."
else
    echo "  ✔ [2/7] .env already exists — skipping."
fi

# ── 3. Start Sail (app + MySQL + Redis) ──────────────────────────────────────
echo "▶ [3/7] Starting Sail services..."
$SAIL up -d
echo "  ✔ Sail started."

# ── 4. Wait for MySQL to be ready ────────────────────────────────────────────
echo "▶ [4/7] Waiting for MySQL..."
for i in $(seq 1 30); do
    if $SAIL artisan migrate:status > /dev/null 2>&1; then
        break
    fi
    printf "."
    sleep 2
done
echo ""
echo "  ✔ MySQL is ready."

# ── 5. Generate APP_KEY if missing ───────────────────────────────────────────
if ! grep -q '^APP_KEY=base64:' .env; then
    echo "▶ [5/7] Generating APP_KEY..."
    $SAIL artisan key:generate
    echo "  ✔ APP_KEY generated."
else
    echo "  ✔ [5/7] APP_KEY already set — skipping."
fi

# ── 6. Run migrations ────────────────────────────────────────────────────────
echo "▶ [6/7] Running database migrations..."
$SAIL artisan migrate --force
$SAIL artisan storage:link --force 2>/dev/null || true
echo "  ✔ Migrations complete."

# ── 7. Install NPM packages and build frontend ───────────────────────────────
echo "▶ [7/7] Building frontend assets..."
$SAIL npm ci
$SAIL npm run build
echo "  ✔ Frontend built."

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║              ✅  Setup complete!             ║"
echo "║                                              ║"
echo "║  👉 http://localhost/setup                   ║"
echo "║     Configure the store and create the       ║"
echo "║     first administrator account.             ║"
echo "╚══════════════════════════════════════════════╝"
echo ""
