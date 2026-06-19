#!/usr/bin/env bash
# render.sh — deploy this app to Render's free tier using render.yaml.
# Requires: git (repo must be pushed to GitHub — Render builds from a connected repo, not a local
# upload), Docker (only to generate APP_KEY). Render CLI is optional; this script falls back to
# clear manual instructions if it isn't installed, since blueprint deploys normally go through
# Render's dashboard anyway.
#
# What this script does:
#   1. Generates a production APP_KEY locally and prints it.
#   2. Lists every secret render.yaml expects you to set in the dashboard (marked `sync: false`).
#   3. If the Render CLI is installed, attempts `render blueprint launch`; otherwise prints the
#      dashboard steps (New → Blueprint → connect this repo).
#
# What it does NOT do: provision a database. Render has no free managed MySQL/Postgres — point
# DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD at an external instance you already host.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    echo "WARNING: you have uncommitted changes. Render deploys from the pushed branch, so they won't be included." >&2
fi

echo "==> Generating a production APP_KEY (paste this into Render's APP_KEY variable)..."
GENERATED_KEY="base64:$(docker run --rm php:8.4-cli-bookworm php -r 'echo base64_encode(random_bytes(32));')"
echo ""
echo "  APP_KEY=${GENERATED_KEY}"
echo ""

cat <<'EOF'
==> Secrets you must set in the Render dashboard (render.yaml marks these `sync: false`,
    meaning Render will not auto-fill them):

    APP_KEY        the value generated above
    APP_URL        https://<your-service>.onrender.com (Render assigns this on first deploy —
                    set it after the service is created, then redeploy)
    DB_HOST        your external MySQL/Postgres host
    DB_PORT        usually 3306 (MySQL) or 5432 (Postgres)
    DB_DATABASE    your database name
    DB_USERNAME
    DB_PASSWORD

    Optional (only if you want real-time notifications or persistent file uploads — see the
    comments in render.yaml / .env.render.example):
    PUSHER_APP_ID, PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_CLUSTER,
    VITE_PUSHER_APP_KEY, VITE_PUSHER_APP_CLUSTER
    AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET
EOF
echo ""

if command -v render > /dev/null 2>&1; then
    echo "==> Render CLI found — attempting blueprint launch..."
    if ! render blueprint launch; then
        echo ""
        echo "Blueprint launch failed or needs interactive input. Finish it from the dashboard instead:"
        echo "  Render dashboard → New → Blueprint → connect this repo → fill in the secrets above."
    fi
else
    echo "==> Render CLI not installed. Deploy from the dashboard instead:"
    echo "  1. Push this repo to GitHub (if you haven't already)."
    echo "  2. Render dashboard → New → Blueprint → connect the repo."
    echo "  3. Fill in the secrets listed above."
    echo "  4. Deploy — migrations run automatically on container start."
fi

echo ""
echo "Once deployed, visit https://<your-service>.onrender.com/setup to configure the store and"
echo "create the first administrator."
