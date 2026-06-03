# E-Commerce Platform Template

A production-ready, product-agnostic Laravel e-commerce starter: catalog with variants, inventory, cart, pay-by-transfer checkout with proof verification, admin dashboard, and real-time notifications.

## Tech stack

- **Backend:** Laravel 11, PHP 8.4
- **Database:** MySQL 8
- **Cache / sessions / queues:** Redis
- **Frontend:** Blade, Tailwind CSS v4, Alpine.js
- **Realtime:** Pusher Channels, Laravel Echo
- **Infrastructure:** Docker Compose quickstart + Laravel Sail

## Quick start (Docker Compose)

```bash
cp .env.example .env
docker compose -f docker/docker-compose.yml up --build
```

Then create the first administrator (one-time):

```bash
docker compose -f docker/docker-compose.yml exec app php artisan app:init-admin
```

Or visit `http://localhost:8080/setup` before any admin exists.

Build frontend assets (host or inside container):

```bash
npm ci && npm run build
```

Configure the store in admin: **categories → products → payment methods** (payment methods are required for checkout).

## Quick start (Laravel Sail)

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan app:init-admin
npm ci && npm run build
```

Sail runs **php artisan serve** and a **queue worker** via supervisord.

## First-run admin

| Method | Command / URL |
|--------|----------------|
| CLI (preferred) | `php artisan app:init-admin` |
| Web fallback | `/setup` (404 after first admin) |

- Strong password required (12+ chars, mixed case, numbers, symbols).
- No demo users or catalog seed data.
- Public registration always creates `user` role only.
- **Admin password changes are CLI-only:** `php artisan admin:change-password` (no admin account UI).

## Admin customization

| Area | Route |
|------|--------|
| Favicon & logo | `/admin/settings/branding` |
| Payment methods | `/admin/settings/payments` |
| Category icons | `/admin/categories` (upload on create/edit) |

## Environment variables

| Variable | Purpose |
|----------|---------|
| `APP_NAME` | Application name |
| `STORE_NAME` | Storefront display name |
| `CURRENCY` / `CURRENCY_SYMBOL` | Money formatting |
| `DB_*` | MySQL connection |
| `REDIS_*` or `REDIS_URL` | Redis for session, cache, queue |
| `SESSION_DRIVER` | Use `redis` in production |
| `CACHE_STORE` | Use `redis` |
| `QUEUE_CONNECTION` | Use `redis` + worker process |
| `BROADCAST_CONNECTION` | `pusher` (or `log` for local smoke tests) |
| `PUSHER_*` / `VITE_PUSHER_*` | Realtime notifications |
| `FILESYSTEM_DISK` | Default disk |
| `FILESYSTEM_PRODUCT_DISK` | Product / QR images (`public` or `s3`) |
| `FILESYSTEM_PRIVATE_DISK` | Payment proofs (`private` or `s3`) |

See [docs/LOCAL-ENV.md](docs/LOCAL-ENV.md) for a full local setup guide, plus [`.env.example`](.env.example) and [`.env.render.example`](.env.render.example).

## Deploy on Render

Pro blueprint: web + worker + MySQL + Redis + S3. Full guide: [docs/DEPLOY-RENDER.md](docs/DEPLOY-RENDER.md)

## Development

```bash
./vendor/bin/sail test
./vendor/bin/sail artisan migrate
./vendor/bin/sail logs -f
```

## Documentation

| File | Description |
|------|-------------|
| [projectSpec.md](projectSpec.md) | Architecture and flows |
| [skill.md](skill.md) | Agent domain reference |
| [agent.md](agent.md) | How AI should work in this repo |
| [docs/LOCAL-ENV.md](docs/LOCAL-ENV.md) | Local `.env` configuration |
| [docs/DEPLOY-RENDER.md](docs/DEPLOY-RENDER.md) | Render Pro deployment |
| [docs/UPSTASH-PUSHER.md](docs/UPSTASH-PUSHER.md) | Redis + Pusher setup |

## Security

- Session auth, CSRF on web routes, FormRequest validation
- Rate limits on registration, checkout, payment upload
- `role` not mass-assignable; admin only via init command or `/setup`
- Inventory `lockForUpdate()` on checkout; SHA-256 duplicate payment proof detection

## License

Use as a template for your own store projects.
