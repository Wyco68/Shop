# Local `.env` setup guide

This guide explains how to configure [`.env`](../.env) for local development with **Laravel Sail** (recommended) or the **`docker/`** quickstart.

Start from the template:

```bash
cp .env.example .env
```

Then generate an application key (if empty):

```bash
./vendor/bin/sail artisan key:generate
# or, without Sail:
php artisan key:generate
```

---

## How local Docker networking works

When you use Sail or `docker/docker-compose.yml`, the **app container** talks to services by **Docker service names**, not `localhost`.

| Service | Set in `.env` | Inside container |
|---------|----------------|------------------|
| MySQL | `DB_HOST=mysql` | Port `3306` (internal) |
| Redis | `REDIS_HOST=redis` | Port `6379` (internal) |

`FORWARD_DB_PORT` and `FORWARD_REDIS_PORT` only map ports on **your machine** (for GUI clients like TablePlus or `redis-cli`). They do **not** change what Laravel uses inside Docker.

If host ports are already taken, use different forwards (already defaulted in `.env.example`):

```env
FORWARD_DB_PORT=3307    # host → container MySQL 3306
FORWARD_REDIS_PORT=6380 # host → container Redis 6379
```

---

## Minimum working local `.env` (Sail)

After `cp .env.example .env`, these values are correct for Sail out of the box:

```env
APP_NAME=E-Commerce
STORE_NAME="${APP_NAME}"
CURRENCY=USD
CURRENCY_SYMBOL=$

APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=sail
DB_PASSWORD=password

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

FILESYSTEM_DISK=local
FILESYSTEM_PRODUCT_DISK=public
FILESYSTEM_PRIVATE_DISK=private

MAIL_MAILER=log
```

**Do not** set `DB_HOST=127.0.0.1` when using Sail — the app runs inside Docker and must use `mysql`.

---

## `docker/` quickstart differences

If you use `docker compose -f docker/docker-compose.yml up` instead of Sail:

| Variable | Value |
|----------|--------|
| `APP_URL` | `http://localhost:8080` |
| `DB_HOST` | `mysql` (unchanged) |
| `REDIS_HOST` | `redis` (unchanged) |

The compose file injects the same DB/Redis hostnames; only the public URL port changes.

---

## Section-by-section reference

### Application & store branding

| Variable | Local example | Notes |
|----------|---------------|--------|
| `APP_NAME` | `E-Commerce` | Laravel app name, emails |
| `STORE_NAME` | `"${APP_NAME}"` or `My Shop` | Shown in layouts/footer |
| `CURRENCY` | `USD` | Stored on orders |
| `CURRENCY_SYMBOL` | `$` | Used by `<x-money>` |
| `APP_URL` | `http://localhost` (Sail) or `http://localhost:8080` (docker/) | Must match how you open the site in the browser |
| `APP_KEY` | *(generated)* | Required; run `key:generate` once |

### Database (MySQL)

Must match [compose.yaml](../compose.yaml) / Sail MySQL service:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=sail
DB_PASSWORD=password
```

After containers are up:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan app:init-admin
```

### Redis (sessions, cache, queues)

Required for the default local stack:

```env
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
```

Sail starts a **queue worker** via supervisord. If queues seem stuck, confirm `QUEUE_CONNECTION=redis` and check `./vendor/bin/sail logs`.

**Optional:** use a single URL instead of host/port:

```env
# REDIS_URL=redis://redis:6379
# REDIS_CLIENT=predis
```

### Realtime (Pusher) — optional locally

Default in `.env.example` is `BROADCAST_CONNECTION=pusher`. Without Pusher credentials, live push notifications will not work, but the app still runs; notifications are stored in the database.

**Option A — skip realtime (simplest):**

```env
BROADCAST_CONNECTION=log
```

Leave `PUSHER_*` empty. Rebuild assets only if you change Vite-related vars:

```bash
npm run build
```

**Option B — full realtime:**

1. Create a free app at [pusher.com](https://pusher.com).
2. Fill in:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-key
PUSHER_APP_SECRET=your-secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_PUSHER_SCHEME=https
```

3. Rebuild frontend: `npm run build` (or `sail npm run build`).

See [UPSTASH-PUSHER.md](UPSTASH-PUSHER.md) for more detail (Redis + Pusher on cloud; same Pusher vars apply locally).

### Mail

Local default logs mail instead of sending:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### File storage

Local uploads stay on disk:

```env
FILESYSTEM_DISK=local
FILESYSTEM_PRODUCT_DISK=public
FILESYSTEM_PRIVATE_DISK=private
```

Run once after clone:

```bash
./vendor/bin/sail artisan storage:link
```

Product images → `storage/app/public`. Payment proofs → `storage/app/private` (admin-only download).

### AWS / S3

Leave empty for local. Only needed for production (e.g. Render) persistent uploads:

```env
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_BUCKET=
```

### Sail user IDs (Linux / WSL)

If you see permission errors on `storage/` or `bootstrap/cache`:

```env
WWWGROUP=1000
WWWUSER=1000
```

Match your host user: `id -u` and `id -g`, then restart Sail.

---

## Checklist after editing `.env`

1. **Start stack:** `./vendor/bin/sail up -d`
2. **Key:** `./vendor/bin/sail artisan key:generate` (if `APP_KEY` empty)
3. **Migrate:** `./vendor/bin/sail artisan migrate`
4. **Admin:** `./vendor/bin/sail artisan app:init-admin` (or `/setup` once)
5. **Assets:** `npm ci && npm run build`
6. **Storage link:** `./vendor/bin/sail artisan storage:link`
7. **Open:** `APP_URL` in the browser (Sail default port **80** → `http://localhost`)

---

## Renamed database (`carpart` → `ecommerce`)

If you previously ran Sail with `DB_DATABASE=carpart`, the MySQL **volume** still only grants `sail` access to `carpart`. After changing `.env` to `ecommerce`, you may see:

`Access denied for user 'sail'@'%' to database 'ecommerce'`

**Fix A — keep existing volume** (create DB + grant):

```bash
./vendor/bin/sail exec mysql mysql -uroot -p"${DB_PASSWORD}" -e "
CREATE DATABASE IF NOT EXISTS ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ecommerce.* TO 'sail'@'%';
FLUSH PRIVILEGES;
"
./vendor/bin/sail artisan migrate
```

**Fix B — fresh MySQL** (deletes all local DB data):

```bash
./vendor/bin/sail down -v
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan app:init-admin
```

**Fix C — stay on old name:** set `DB_DATABASE=carpart` in `.env` if you do not need the rename.

---

## Common mistakes

| Symptom | Fix |
|---------|-----|
| `Access denied` to database `ecommerce` | Volume still on `carpart`; see [Renamed database](#renamed-database-carpart--ecommerce) |
| `Connection refused` to MySQL/Redis | Use `DB_HOST=mysql`, `REDIS_HOST=redis`, not `127.0.0.1` |
| Port bind error on `sail up` | Raise `FORWARD_DB_PORT` / `FORWARD_REDIS_PORT` (e.g. 3307, 6380) |
| 419 / session issues | Clear config: `sail artisan optimize:clear`; ensure Redis is running |
| CSRF OK but no live notifications | Set Pusher vars or use `BROADCAST_CONNECTION=log` |
| `$` wrong on storefront | Set `CURRENCY` and `CURRENCY_SYMBOL` |
| Checkout has no payment methods | Add them in **Admin → Payment Methods** (no seeders) |

---

## Running without Docker (advanced)

If PHP, MySQL, and Redis are installed on the host:

```env
DB_HOST=127.0.0.1
DB_PORT=3307          # or your local MySQL port
REDIS_HOST=127.0.0.1
REDIS_PORT=6380       # or your local Redis port
APP_URL=http://127.0.0.1:8000
```

Run `php artisan serve`, `php artisan queue:work redis`, and ensure MySQL/Redis credentials match your local installs. Docker values in `.env.example` are tuned for Sail, not bare-metal defaults.

---

## Related docs

- [README.md](../README.md) — quick start
- [DEPLOY-RENDER.md](DEPLOY-RENDER.md) — production `.env` on Render
- [.env.render.example](../.env.render.example) — production template
