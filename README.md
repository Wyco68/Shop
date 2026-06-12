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
- Pusher Channels + Laravel Echo
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

### 8. Open the setup page

Visit **[http://localhost/setup](http://localhost/setup)** to configure the store and create the first administrator account.

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
| No live notifications locally | Set Pusher vars in `.env` or use `BROADCAST_CONNECTION=log` for log-only. |
| Frontend shows blank / missing styles | Run `sail npm ci && sail npm run build` (or `sail npm run dev` for HMR). |

---

## Admin Setup

- After `migrate`, visit **`/setup`** once (no admin exists yet). Configure store name, **base currency** (ISO code, symbol, position), and the first administrator account. `/setup` returns 404 after the first admin is created.
- Until setup completes, the app redirects all visitors to `/setup`.
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

Deploy on **Render** with the Pro blueprint in [`render.yaml`](render.yaml) (web + worker + MySQL).

1. Push the repo to GitHub.
2. Render → **New** → **Blueprint** → connect the repo.
3. Set secrets: `APP_URL`, `REDIS_URL`, `PUSHER_*`, `VITE_PUSHER_*`, `AWS_*` (S3 uploads), and a **new** `APP_KEY` from `./vendor/bin/sail artisan key:generate --show` (production-only; never reuse local).
4. Deploy — migrations run on container start; no seeders.
5. Open **`/setup`** once to configure the store and create the first administrator.

Production env template: [`.env.render.example`](.env.render.example).

Required production drivers: `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `BROADCAST_CONNECTION=pusher`, plus a queue worker (included in the blueprint).

The production Docker image is built from [`Dockerfile`](Dockerfile) (root). It is **not** used for local development — Sail uses its own image from `vendor/laravel/sail`.

---

## Production Security Checklist

Complete **before** exposing the app to the public internet:

| Item | Local (`.env.example`) | Production |
|------|------------------------|------------|
| `APP_DEBUG` | `true` (dev only) | **`false`** — never enable in production |
| `APP_KEY` | Generate per machine | **Unique** key per environment (never copy from local) |
| `LOG_LEVEL` | `debug` | `info`, `warning`, or `error` |
| Admin bootstrap | Visit `/setup` once | Complete `/setup` **before** DNS goes live |
| Database / Redis | Forwarded ports OK locally | **Private network only** — do not expose `3306`/`6379` publicly |
| Reverse proxy | Optional locally | Terminate TLS at edge; restrict `trustProxies` to real proxy IPs |
| `.env` | Gitignored | Store secrets in host dashboard only — never commit |

Templates: [`.env.example`](.env.example) (local), [`.env.render.example`](.env.render.example) (production).
