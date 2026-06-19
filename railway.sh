#!/usr/bin/env bash
# railway.sh — deploy this app to Railway using the free trial credit / lowest-cost plan.
# Requires: Railway CLI (https://docs.railway.com/guides/cli), Docker (only to generate APP_KEY).
#
# What this script does:
#   1. Logs in / links a Railway project (interactive, first run only).
#   2. Fills .env.railway from .env.railway.example and generates APP_KEY if missing.
#   3. Pushes every non-blank var in .env.railway to the Railway service via `railway variables`.
#   4. Deploys with `railway up` and generates a public domain if one doesn't exist yet.
#
# What it does NOT do: provision a database. Add Railway's MySQL plugin yourself in the dashboard
# ("+ New" → Database → MySQL) — see the comment block in .env.railway.example for why this isn't
# automated here (the plugin's exact variable names vary slightly by template version).
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

if ! command -v railway > /dev/null 2>&1; then
    echo "ERROR: Railway CLI not found. Install it (see https://docs.railway.com/guides/cli), then re-run this script." >&2
    exit 1
fi

if ! railway whoami > /dev/null 2>&1; then
    echo "==> Not logged in to Railway — opening login..."
    railway login
fi

if [ ! -f .railway/config.json ] && [ ! -f .railway.json ]; then
    echo "==> No linked Railway project found — initializing..."
    railway init
else
    echo "==> Using existing linked Railway project."
fi

if [ ! -f .env.railway ]; then
    echo "==> Creating .env.railway from .env.railway.example"
    cp .env.railway.example .env.railway
fi

if ! grep -q '^APP_KEY=base64:' .env.railway 2>/dev/null; then
    echo "==> Generating APP_KEY..."
    GENERATED_KEY="base64:$(docker run --rm php:8.4-cli-bookworm php -r 'echo base64_encode(random_bytes(32));')"
    if grep -q '^APP_KEY=' .env.railway; then
        sed -i.bak "s|^APP_KEY=.*|APP_KEY=${GENERATED_KEY}|" .env.railway && rm -f .env.railway.bak
    else
        echo "APP_KEY=${GENERATED_KEY}" >> .env.railway
    fi
    echo "  APP_KEY set in .env.railway."
fi

echo "==> Pushing variables from .env.railway to Railway..."
while IFS='=' read -r key value; do
    [[ -z "$key" || "$key" == \#* ]] && continue
    [[ -z "${value:-}" ]] && continue
    railway variables --set "${key}=${value}"
done < .env.railway

DB_HOST_SET=$(grep '^DB_HOST=' .env.railway | cut -d= -f2-)
if [ -z "$DB_HOST_SET" ]; then
    echo ""
    echo "  WARNING: DB_HOST is blank. Add Railway's MySQL plugin in the dashboard, then either:"
    echo "    - use its 'Add Reference' picker on this service's DB_HOST/DB_PORT/DB_DATABASE/"
    echo "      DB_USERNAME/DB_PASSWORD variables, or"
    echo "    - run: railway variables   (to see the plugin's exact variable names), then"
    echo "      railway variables --set DB_HOST=... --set DB_PORT=... (etc.) yourself."
    echo "  The app will build but fail to migrate/serve correctly until this is set."
    echo ""
fi

echo "==> Deploying..."
railway up

echo "==> Ensuring a public domain exists..."
railway domain || echo "  Could not auto-generate a domain — generate one from the Railway dashboard instead."

echo ""
echo "Done. The container auto-detects its Railway domain and sets APP_URL on boot."
echo "Visit https://<your-railway-domain>/setup to configure the store and create the first administrator."
