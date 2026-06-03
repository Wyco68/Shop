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

2. Copy env and install PHP dependencies:

```bash
cp .env.example .env
composer install
```

3. Start Sail and run migrations:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

4. Build frontend assets:

```bash
npm ci && npm run build
```

5. Open `http://localhost` (`APP_URL` in `.env`).

`.env.example` uses `DB_HOST=mysql` and `REDIS_HOST=redis` for Sail. Do not use `127.0.0.1` for those hosts inside Docker.

| Issue | Fix |
|-------|-----|
| DB/Redis connection refused | Set `DB_HOST=mysql`, `REDIS_HOST=redis` |
| Port already in use | Raise `FORWARD_DB_PORT` / `FORWARD_REDIS_PORT` in `.env` |
| No live notifications locally | Set Pusher vars or `BROADCAST_CONNECTION=log` |

## Admin Setup

- Run: `./vendor/bin/sail artisan app:init-admin`
- Or visit `/setup` once before any admin exists (404 after first admin).
- Password: 12+ chars with mixed case, numbers, and symbols.
- No demo users or catalog seed data.
- Add **payment methods** in admin before checkout works.
- Admin password changes: CLI only — `./vendor/bin/sail artisan admin:change-password` (no admin account UI).

## Testing

```bash
./vendor/bin/sail test
```

## Deployment

Deploy on **Render** with the Pro blueprint in [`render.yaml`](render.yaml) (web + worker + MySQL).

1. Push the repo to GitHub.
2. Render → **New** → **Blueprint** → connect the repo.
3. Set secrets: `APP_KEY`, `APP_URL`, `REDIS_URL`, `PUSHER_*`, `VITE_PUSHER_*`, `AWS_*` (S3 uploads).
4. Deploy (migrations run on start; no seeders).
5. Create the first admin once: `php artisan app:init-admin` (Render shell) or `/setup`.

Production env template: [`.env.render.example`](.env.render.example).

Required production drivers: `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `BROADCAST_CONNECTION=pusher`, plus a queue worker (included in the blueprint).
