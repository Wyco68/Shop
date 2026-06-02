# CarPart Production Hosting Guide

This guide explains how to run CarPart in production with persistent data, background jobs, object storage, and realtime notifications.

It is designed for the current stack in this repo:

- Laravel + Blade app in Docker
- MySQL database
- Redis (sessions/cache/queues)
- Pusher Channels (realtime)
- Optional S3-compatible object storage for uploads

The existing `docs/HOSTING-RENDER.md` is optimized for portfolio demo mode (free tier + SQLite). This guide is for production.

---

## 1) Production architecture (recommended)

- **Web service:** Laravel app (Docker), autoscaling if available
- **Worker service:** `php artisan queue:work redis --sleep=1 --tries=3 --timeout=120`
- **MySQL:** managed DB (Render Postgres/MySQL alternative, Railway, Aiven, Neon-compatible MySQL provider, etc.)
- **Redis:** Upstash Redis (or managed Redis in same region)
- **Storage:** S3/R2/DO Spaces for persistent uploads
- **Realtime:** Pusher Channels

Keep all services in the same/nearby region to reduce latency.

---

## 2) Pre-deploy checklist

- [ ] Domain and DNS ready
- [ ] SSL/TLS enabled
- [ ] Backup plan for DB
- [ ] Separate prod credentials (never reuse local/dev secrets)
- [ ] `APP_KEY` generated for production only
- [ ] Pusher app for production created
- [ ] Redis URL from production Redis (not REST token)

---

## 3) Environment variables (production baseline)

Use this as your production template:

```env
APP_NAME=CarPart
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_NEW_PRODUCTION_KEY
APP_URL=https://yourdomain.com
APP_DEMO_MODE=false

LOG_CHANNEL=stderr
LOG_LEVEL=info

# Database (managed MySQL)
DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=carpart
DB_USERNAME=carpart_user
DB_PASSWORD=strong_password

# Redis (managed)
REDIS_CLIENT=predis
REDIS_URL=rediss://default:password@your-redis-host:6379

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.yourdomain.com

# Broadcast / realtime (Pusher)
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=ap1
PUSHER_SCHEME=https
PUSHER_PORT=443

# Vite build-time vars (must be set before build/deploy)
VITE_PUSHER_APP_KEY=...
VITE_PUSHER_APP_CLUSTER=ap1
VITE_PUSHER_SCHEME=https
VITE_APP_NAME=CarPart

# Persistent uploads
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
AWS_USE_PATH_STYLE_ENDPOINT=false
# AWS_ENDPOINT=... # set if using R2/Spaces/MinIO

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME=CarPart
```

Notes:

- `SESSION_SECURE_COOKIE=true` is correct for real HTTPS production domains.
- Do not use `UPSTASH_REDIS_REST_URL` / `UPSTASH_REDIS_REST_TOKEN` for Laravel sessions/cache.
- `VITE_*` values are compiled into frontend assets at build time; redeploy after changing them.

---

## 4) Render deployment strategy (production)

For Render, use at least:

1. **Web Service** (Docker)
2. **Background Worker** (same image)
3. **Managed database** (or external MySQL)
4. **Managed Redis / Upstash**

### Web service

- Runtime: Docker
- Health check: `/up`
- Auto deploy: enabled
- Set all env vars above

### Worker service

- Same repo + Dockerfile
- Start command:

```bash
php artisan queue:work redis --sleep=1 --tries=3 --timeout=120 --max-time=3600
```

- Use same env vars as web service
- No public port required

---

## 5) Database migration flow

On each production deploy, run:

```bash
php artisan migrate --force
```

Do not run demo seeders in production.

If your platform supports pre-deploy/release command, run migration there.  
If not, run migrations in CI/CD before switching traffic.

---

## 6) Build and release flow

1. Set/verify all production env vars.
2. Deploy image (web + worker).
3. Run migrations.
4. Smoke test critical flows.
5. Monitor logs and queue health for 10-30 minutes.

Recommended CI checks before deploy:

- `php artisan test`
- `npm run build`
- `php artisan config:clear && php artisan route:clear`

---

## 7) Production smoke test checklist

- [ ] `/up` returns 200
- [ ] Login/logout works
- [ ] Add-to-cart and checkout work
- [ ] Payment proof upload stores in S3 disk
- [ ] Admin payment verify updates order status
- [ ] Notification badge updates in realtime (Pusher)
- [ ] Queue jobs process in worker (no backlog growth)
- [ ] Error logs contain no boot/runtime exceptions

---

## 8) Security hardening

- `APP_DEBUG=false`
- Rotate credentials regularly (`APP_KEY`, DB, Redis, Pusher)
- Keep secrets only in platform env store (never commit)
- Enforce HTTPS and secure cookies
- Use least-privilege DB user
- Enable database backups and restore drill

Optional app-level hardening:

- Add `php artisan config:cache` in immutable release step
- Add rate limiting and bot protection at edge
- Add uptime + error alerting (Sentry, platform alerts)

---

## 9) Performance and reliability

- Cache hot queries with Redis
- Offload heavy work to queues
- Keep worker concurrency aligned with DB capacity
- Use CDN for static assets and media
- Keep app, DB, and Redis in same region

If traffic grows:

- Increase web replicas first
- Increase worker count second
- Add read replicas only when needed

---

## 10) Rollback plan

Have a written rollback:

1. Re-deploy previous stable image
2. Disable new feature flags (if any)
3. Restore DB from backup only if migration is destructive
4. Verify `/up`, login, checkout, admin order flow

Never deploy irreversible schema changes without backup + tested rollback.

---

## Related docs

- `docs/HOSTING-RENDER.md` (portfolio/free-tier demo setup)
- `docs/UPSTASH-PUSHER.md` (Redis + Pusher setup details)
- `.env.render.example` (render env template)
