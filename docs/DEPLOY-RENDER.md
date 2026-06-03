# Deploy on Render (Pro)

Production template: **Docker web service**, **background worker**, **managed MySQL**, **Redis** (Upstash or Render), **Pusher**, and **S3-compatible storage** for uploads.

## Architecture

| Component | Render service | Role |
|-----------|----------------|------|
| Web | `ecommerce-web` (Pro) | HTTP, migrations on start |
| Worker | `ecommerce-worker` (Pro) | `queue:work redis` |
| MySQL | `ecommerce-mysql` (Starter+) | Primary database |
| Redis | External `REDIS_URL` | Sessions, cache, queues |
| Storage | S3/R2 bucket | Product images, payment proofs |
| Realtime | Pusher Channels | Notifications |

## Blueprint deploy

1. Push this repository to GitHub.
2. Render Dashboard → **New** → **Blueprint** → connect repo.
3. Render reads [`render.yaml`](../render.yaml) and provisions web, worker, and MySQL.
4. Set sync=false secrets in the dashboard:
   - `APP_KEY` — `php artisan key:generate --show` locally
   - `APP_URL` — `https://your-service.onrender.com`
   - `REDIS_URL` — Upstash Redis TCP URL (`rediss://…`)
   - Pusher credentials
   - AWS/S3 credentials for `FILESYSTEM_*_DISK=s3`
5. Deploy. On first boot, migrations run automatically (no seeders).
6. **Create admin once** (Render Shell):
   ```bash
   php artisan app:init-admin
   ```
   Or open `https://your-app.onrender.com/setup` before any admin exists.
7. In admin: add categories, products, and **payment methods** (required for checkout).

## Environment variables

| Variable | Required | Notes |
|----------|----------|-------|
| `APP_KEY` | Yes | Production-only key |
| `APP_URL` | Yes | HTTPS, no trailing slash |
| `STORE_NAME` | Yes | Storefront branding |
| `CURRENCY` / `CURRENCY_SYMBOL` | Yes | Order currency |
| `DB_*` | Yes | From Render MySQL (Blueprint links automatically) |
| `REDIS_URL` | Yes | Sessions + cache + queue |
| `QUEUE_CONNECTION` | Yes | `redis` |
| `BROADCAST_CONNECTION` | Yes | `pusher` for live notifications |
| `PUSHER_*` / `VITE_PUSHER_*` | Yes | Match Pusher app |
| `AWS_*` | Recommended | Persistent uploads on Pro |

See [`.env.render.example`](../.env.render.example) for a full template.

## Worker service

The worker must use the same `APP_KEY`, database, and `REDIS_URL` as the web service. Blueprint copies env from `ecommerce-web` via `fromService` in `render.yaml`.

## Local parity

Match production drivers locally:

```env
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=pusher
```

Run a queue worker (Sail supervisord or `docker/docker-compose.yml` includes one).

## Optional docs

- [UPSTASH-PUSHER.md](UPSTASH-PUSHER.md) — Redis + Pusher setup details
- [HOSTING-PRODUCTION.md](HOSTING-PRODUCTION.md) — general production checklist
