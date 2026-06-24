# Car Parts E-Commerce Platform

## Overview

- Laravel e-commerce starter for car parts stores.
- Catalog with variants, inventory, cart, and pay-by-transfer checkout with proof verification.
- Admin dashboard and real-time notifications.
- Empty database after migrate — configure catalog and payment methods in admin.

## Tech Stack

- Laravel 12, PHP 8.4, MySQL 8
- Redis (sessions, cache, queues)
- Blade, Tailwind CSS v4, Alpine.js
- Laravel Reverb (self-hosted WebSockets) + Laravel Echo
- Laravel Sail (Docker)

## Prerequisites

**Git** and **Docker Desktop** — that's all. No PHP, Composer, or Node required on your host machine.

> **Windows users:** run all commands inside **WSL 2** (Linux filesystem, e.g. `~/projects/carPart`). Do not clone into `C:\` and run from CMD/PowerShell.

## Quick Start

### 1. Clone

```bash
git clone <repo-url> carPart && cd carPart
```

### 2. Bootstrap PHP vendor (one-time, Docker only — no local PHP needed)

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs --no-interaction
```

This pulls the official Sail Composer image and installs `vendor/` inside your project. After this, you never need host PHP or Composer again — all further commands go through Sail.

### 3. Configure environment

```bash
cp .env.example .env
```

`.env.example` is pre-configured for Sail (`DB_HOST=mysql`, `REDIS_HOST=redis`). No edits needed to boot locally.

### 4. Start services

```bash
./vendor/bin/sail up -d
```

Starts app (PHP 8.4), MySQL 8, and Redis in the background. First run pulls images (~1–2 min).

### 5. Generate app key

```bash
./vendor/bin/sail artisan key:generate
```

Writes a unique `APP_KEY=base64:…` into `.env`. **Never commit `.env`.** Generate a separate key for each environment (local, staging, production).

To print the key without saving (e.g. for Render secrets):

```bash
./vendor/bin/sail artisan key:generate --show
```

Paste the full `base64:…` line into your host's env dashboard (no quotes).

### 6. Run migrations

```bash
./vendor/bin/sail artisan migrate
```

### 7. Build frontend assets

For a one-time production build:

```bash
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

For live HMR during development:

```bash
./vendor/bin/sail npm run dev
```

### 8. Run the setup command

```bash
./vendor/bin/sail artisan store:setup
```

Answer the prompts to configure the store and create the first administrator account. This is CLI-only
— there is no web setup page.

---

## Useful Sail Commands

```bash
# Stop services
./vendor/bin/sail down

# Tail logs
./vendor/bin/sail logs -f

# Run Artisan
./vendor/bin/sail artisan <command>

# Run Composer
./vendor/bin/sail composer <command>

# Run NPM
./vendor/bin/sail npm <command>

# Open a shell inside the container
./vendor/bin/sail shell

# Run tests
./vendor/bin/sail artisan test
```

You can add a shell alias to shorten the command:

```bash
alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
```

---

## Troubleshooting

### Vendor bootstrap fails

| Symptom | Fix |
|---------|-----|
| `docker: command not found` | Install [Docker Desktop](https://www.docker.com/products/docker-desktop/) and ensure it is running. |
| `permission denied` on `/var/www/html` | On Linux, confirm `id -u` is not 0 (root). Run as your normal user. |
| Runs out of memory | `COMPOSER_MEMORY_LIMIT=-1 docker run … composer install …` |
| Slow or SSL errors on Windows | Run inside **WSL 2** on the Linux filesystem, not from `C:\`. |

### `APP_KEY` / sessions (419 errors)

| Symptom | Fix |
|---------|-----|
| `No application encryption key` / 419 on forms | Run `./vendor/bin/sail artisan key:generate` and confirm `.env` has `APP_KEY=base64:…`. |
| Key set but 419 persists | `./vendor/bin/sail artisan optimize:clear`, restart Sail, hard-refresh the browser. |
| Copied key to Render/hosting | Paste the full `base64:…` line; no quotes. Generate a **new** key for production — never reuse local. |
| Accidentally committed a key | Rotate: generate a new key, update `.env`/host secrets, run `optimize:clear`, invalidate sessions. |

### App / Sail

| Symptom | Fix |
|---------|-----|
| DB/Redis connection refused | Confirm `DB_HOST=mysql` and `REDIS_HOST=redis` in `.env` (not `127.0.0.1`). |
| Port `80` or `3306` already in use | Set `APP_PORT`, `FORWARD_DB_PORT`, or `FORWARD_REDIS_PORT` in `.env` before `sail up`. |
| `sail up` fails: image not found | Run the Step 2 bootstrap first — `vendor/laravel/sail` must exist. |
| No live notifications locally | Set `BROADCAST_CONNECTION=reverb` + `REVERB_*`/`VITE_REVERB_*` in `.env` and run `sail artisan reverb:start`, or use `BROADCAST_CONNECTION=log` for log-only. |
| Frontend shows blank / missing styles | Run `sail npm ci && sail npm run build` (or `sail npm run dev` for HMR). |

---

## Admin Setup

- After `migrate`, run `php artisan store:setup` once (no admin exists yet). Configure store name, **base currency** (ISO code, symbol, position), and the first administrator account. The command refuses to run again once the first admin exists.
- Until setup completes, the app returns a 503 to all web visitors — there is no `/setup` web page to visit.
- **Currency is set once** during setup, then `currency_locked` prevents changes via admin/API. Emergency override: `./vendor/bin/sail artisan currency:force-change` (interactive confirmation, logged).
- Password: 12+ chars with mixed case, numbers, and symbols.
- No demo users or catalog seed data.
- Add **payment methods** in admin before checkout works.
- Admin password changes: **Admin → Security** (re-authentication required) or CLI — `./vendor/bin/sail artisan admin:change-password`.

---

## Testing

Run the suite inside Sail:

```bash
./vendor/bin/sail artisan test
```

Filter examples:

```bash
./vendor/bin/sail artisan test --filter=CartTest
```

Tests use a separate in-memory SQLite database — they do not touch your local MySQL data.

---

## Deployment

Three deployment targets, all built from the same root [`Dockerfile`](Dockerfile) (not used for local dev — Sail uses its own image from `vendor/laravel/sail`):

| Target | Cost | Database | Redis | Queue worker | Guide |
|--------|------|-----------|-------|---------------|-------|
| Render | Free tier | External (Render has no free managed DB) | None — file sessions, DB cache | None — `QUEUE_CONNECTION=sync` | below |
| Railway | One-time trial credit, then paid | Railway's MySQL plugin (one-click) | None — same as Render | None — `QUEUE_CONNECTION=sync` | below |
| Self-hosted VPS | Whatever the VPS costs | Docker Compose MySQL | Docker Compose Redis | Dedicated container | [`DEPLOYMENT.md`](DEPLOYMENT.md) |

### Render (free tier)

```bash
./render.sh
```

Prints a generated `APP_KEY` and the exact list of dashboard secrets to fill in (`APP_URL`, `DB_*` — point at an external MySQL/Postgres, Render doesn't provide one for free), then launches the [`render.yaml`](render.yaml) blueprint if the Render CLI is installed, or walks you through the dashboard flow if not. Env reference: [`.env.render.example`](.env.render.example).

### Railway

```bash
./railway.sh
```

Links/creates a Railway project, generates `APP_KEY`, pushes [`.env.railway.example`](.env.railway.example) (copy it to `.env.railway` and edit first) as variables, and deploys via [`railway.toml`](railway.toml). Add Railway's MySQL plugin from the dashboard before running it — see the comment block in `.env.railway.example` for why that one step isn't automated.

Both free-tier targets skip Redis and the queue worker to fit the free/lowest-cost plan: sessions use the `file` driver, cache uses the `database` driver (migration already included), and jobs run synchronously in-request rather than in the background. The scheduled daily notification-prune task (`routes/console.php`) also doesn't run automatically on either platform without a worker/cron service — trigger it manually or upgrade if you need it.

After deploying to either platform, run `php artisan store:setup` once via the platform's shell/console
(Render: Shell tab on the service; Railway: `railway run php artisan store:setup`) to configure the store
and create the first administrator. There is no `/setup` web page — until this command runs, the app
returns a 503 to all visitors.

---

## VPS Deployment

Self-hosted stack for a bare VPS (DigitalOcean, Hetzner, etc.) — app + queue worker + scheduler + MySQL + Redis, all in Docker. TLS is handled by Nginx + certbot on the host, outside this stack (see `DEPLOYMENT.md` "Phase 6"). Requires **Docker Engine** + the **Docker Compose plugin**, plus Nginx + certbot on the server.

> Starting from a brand-new, unconfigured server (user setup, SSH hardening, firewall, fail2ban, Docker install, backups)? See [`DEPLOYMENT.md`](DEPLOYMENT.md) for the full walkthrough. The steps below assume that part is already done.

1. Clone the repo on the VPS:

   ```bash
   git clone <repo-url> carPart && cd carPart
   ```

2. Create the environment file:

   ```bash
   cp .env.vps.example .env.vps
   ```

   Edit `.env.vps`: set `DB_PASSWORD` and `APP_URL`. Then set up the Nginx + certbot reverse proxy for your domain — see `DEPLOYMENT.md` "Phase 6".

3. Build the image, then generate a production `APP_KEY` (never reuse one from another environment):

   ```bash
   docker compose -f docker-compose.vps.yml --env-file .env.vps build
   docker compose -f docker-compose.vps.yml --env-file .env.vps run --rm app php artisan key:generate --show
   ```

   Paste the printed `base64:...` value into `APP_KEY=` in `.env.vps`.

4. Start the stack:

   ```bash
   docker compose -f docker-compose.vps.yml --env-file .env.vps up -d
   ```

   The `app` container runs migrations and storage setup on boot; `worker` and `scheduler` wait for it to report healthy before starting, so there's no startup race. Uploaded files persist in the `app-storage` volume; the database in `mysql-data`.

5. Configure the store and create the first administrator (CLI-only — there is no `/setup` web page):

   ```bash
   docker compose -f docker-compose.vps.yml exec app php artisan store:setup
   ```

   Also restrict `trustProxies` in `bootstrap/app.php` to your reverse proxy before going live — see
   `DEPLOYMENT.md` "Phase 6" for why the default `'*'` is unsafe in production and how to find the
   correct value for this stack.

Useful commands:

```bash
# Tail logs
docker compose -f docker-compose.vps.yml logs -f

# Run artisan inside the running app container
docker compose -f docker-compose.vps.yml exec app php artisan <command>

# Rebuild and redeploy after a git pull
docker compose -f docker-compose.vps.yml --env-file .env.vps up -d --build
```

By default uploads are stored on the local `app-storage` volume (`FILESYSTEM_DISK=local`). Set `FILESYSTEM_DISK`/`FILESYSTEM_PRODUCT_DISK`/`FILESYSTEM_PRIVATE_DISK=s3` and fill in `AWS_*` in `.env.vps` to use S3 instead. Real-time notifications run on the self-hosted `reverb` container (`REVERB_*` / `VITE_REVERB_*` in `.env.vps`) — set `BROADCAST_CONNECTION=log` to disable them instead.

---

## Production Security Checklist

Complete **before** exposing the app to the public internet:

| Item | Local (`.env.example`) | Production |
|------|------------------------|------------|
| `APP_DEBUG` | `true` (dev only) | **`false`** — never enable in production |
| `APP_KEY` | Generate per machine | **Unique** key per environment (never copy from local) |
| `LOG_LEVEL` | `debug` | `info`, `warning`, or `error` |
| Admin bootstrap | Run `php artisan store:setup` once | Run `store:setup` **before** DNS goes live |
| Database / Redis | Forwarded ports OK locally | **Private network only** — do not expose `3306`/`6379` publicly |
| Reverse proxy | Optional locally | Terminate TLS at edge; restrict `trustProxies` to real proxy IPs |
| `.env` | Gitignored | Store secrets in host dashboard only — never commit |

Templates: [`.env.example`](.env.example) (local), [`.env.render.example`](.env.render.example) (production).
