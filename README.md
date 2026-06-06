# E-Commerce Platform Template

## Overview

- Laravel e-commerce starter for any product-based store.
- Catalog with variants, inventory, cart, and pay-by-transfer checkout with proof verification.
- Admin dashboard and real-time notifications.
- Empty database after migrate — configure catalog and payment methods in admin.

## Tech Stack

- Laravel 11, PHP 8.4, MySQL 8
- Redis (sessions, cache, queues)
- Blade, Tailwind CSS v4, Alpine.js
- Pusher Channels + Laravel Echo
- Laravel Sail (Docker)

## Quick Start

1. Clone and enter the project:

```bash
git clone <repo-url> ecommerce && cd ecommerce
```

2. Copy env and install PHP dependencies (requires **PHP 8.2+** and **Composer** on your machine, or use the Docker option in Troubleshooting):

```bash
cp .env.example .env
composer install --no-interaction
```

3. Start Sail, generate `APP_KEY`, and migrate:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

`key:generate` writes a unique `APP_KEY=base64:…` into `.env`. **Do not commit `.env`.** Use a separate key for each machine (local, staging, production).

To print a key without saving (e.g. Render secrets):

```bash
./vendor/bin/sail artisan key:generate --show
```

Paste the full line into your host’s env UI (no quotes). Production template: [`.env.render.example`](.env.render.example).

4. Build frontend assets:

```bash
npm ci && npm run build
```

5. Open `http://localhost` (`APP_URL` in `.env`).

**Tests:** `./vendor/bin/sail artisan test` uses a temporary in-app key when `.env` has no `APP_KEY`; you do not need to set one only for PHPUnit.

`.env.example` uses `DB_HOST=mysql` and `REDIS_HOST=redis` for Sail. Do not use `127.0.0.1` for those hosts inside Docker.

### Troubleshooting

#### `composer install` fails (first clone)

| Symptom | Fix |
|---------|-----|
| `composer: command not found` | Install Composer: [getcomposer.org](https://getcomposer.org/download/). On Ubuntu/WSL: `sudo apt install composer` or use the Docker install below. |
| `php: command not found` or PHP below 8.2 | Install PHP 8.2+ (8.4 recommended). Ubuntu/WSL: `sudo apt install php-cli php-mbstring php-xml php-curl php-zip php-bcmath php-tokenizer unzip git` |
| Missing PHP extension (`ext-*`) | Install the matching package (e.g. `php-mbstring`, `php-xml`, `php-curl`, `php-zip`) and run `composer install` again. |
| `Your requirements could not be resolved` | Update Composer: `composer self-update`, then retry. Ensure you are in the project root (folder with `composer.json`). |
| Prompt about `allow-plugins` | Run `composer install --no-interaction` or answer `yes` when asked to trust plugins. |
| Runs out of memory | `COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction` |
| Slow or SSL errors on Windows | Clone and run commands inside **WSL** (Linux filesystem, e.g. `~/projects/ecommerce`), not from `C:\` via CMD. |
| No local PHP (Docker only) | From the project root, install vendors with Sail’s Composer image (Docker must be running): |

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs --no-interaction
```

Then continue with `./vendor/bin/sail up -d`. After Sail is up, prefer `./vendor/bin/sail composer …` instead of host `composer`.

#### `APP_KEY` / sessions (419 errors)

| Symptom | Fix |
|---------|-----|
| `No application encryption key` / 419 on forms | Run `./vendor/bin/sail artisan key:generate` and confirm `.env` has `APP_KEY=base64:…` (not empty). |
| Key set but 419 persists | `./vendor/bin/sail artisan optimize:clear`, restart Sail, hard-refresh the browser. |
| Copied key to Render/hosting | Use `php artisan key:generate --show` locally; paste **one** line, include the `base64:` prefix, no quotes. Generate a **new** key for production — do not reuse your local key. |
| Accidentally committed a key | Rotate: generate a new key, update `.env`/host secrets, run `optimize:clear`, invalidate user sessions (log in again). |

#### App / Sail

| Symptom | Fix |
|---------|-----|
| DB/Redis connection refused | Set `DB_HOST=mysql`, `REDIS_HOST=redis` |
| Port already in use | Raise `FORWARD_DB_PORT` / `FORWARD_REDIS_PORT` in `.env` |
| No live notifications locally | Set Pusher vars or `BROADCAST_CONNECTION=log` |

## Admin Setup

- After `migrate`, visit **`/setup`** once (no admin exists yet). Configure store name, **base currency** (ISO code, symbol, position), and the first administrator account. `/setup` returns 404 after the first admin is created.
- Until setup completes, the app redirects all visitors to `/setup` (no auto-created admin).
- **Currency is set once** during setup, then `currency_locked` prevents changes via admin/API. Emergency override: `./vendor/bin/sail artisan currency:force-change` (interactive confirmation, logged).
- Password: 12+ chars with mixed case, numbers, and symbols.
- No demo users or catalog seed data.
- Add **payment methods** in admin before checkout works.
- Admin password changes: **Admin → Security** (re-authentication required) or CLI — `./vendor/bin/sail artisan admin:change-password`.

## Testing

Run the suite **inside Sail** (recommended). Host `php artisan test` requires the `pdo_mysql` PHP extension and a reachable MySQL instance matching `.env`:

```bash
./vendor/bin/sail artisan test
```

Filter examples:

```bash
./vendor/bin/sail artisan test --filter=CartTest
```

**Host PHP without Sail:** install `php-mysql` (Ubuntu/WSL: `sudo apt install php-mysql`) and ensure `DB_HOST`/`DB_PORT` point at a running MySQL. Otherwise every feature test fails with `could not find driver`.

## Deployment

Deploy on **Render** with the Pro blueprint in [`render.yaml`](render.yaml) (web + worker + MySQL).

1. Push the repo to GitHub.
2. Render → **New** → **Blueprint** → connect the repo.
3. Set secrets: `APP_URL`, `REDIS_URL`, `PUSHER_*`, `VITE_PUSHER_*`, `AWS_*` (S3 uploads), and a **new** `APP_KEY` from `./vendor/bin/sail artisan key:generate --show` (production-only; never reuse local).
4. Deploy (migrations run on start; no seeders).
5. Open **`/setup`** once to configure the store and create the first administrator.

Production env template: [`.env.render.example`](.env.render.example).

Required production drivers: `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `BROADCAST_CONNECTION=pusher`, plus a queue worker (included in the blueprint).

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
