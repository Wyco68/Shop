# Deploy CarPart on Render

> **Portfolio-ready** — one free Web Service, SQLite demo data, no Redis/Reverb required.  
> Full local dev still uses [Laravel Sail](../README.md).

---

## At a glance

| Item | Value |
|------|--------|
| **Platform** | [Render](https://render.com) Web Service (Docker) |
| **Cost** | Free tier (sleeps after ~15 min idle; first load ~30–60s) |
| **Database** | SQLite (file in container; re-seeded on deploy) |
| **Realtime** | Off (`BROADCAST_CONNECTION=log`) — notifications load from DB |
| **Demo logins** | See [Demo accounts](#demo-accounts) |

```mermaid
flowchart TB
    subgraph Render["Render (free tier)"]
        WEB["Web Service<br/>Docker + PHP 8.4"]
        DB[("SQLite<br/>database.sqlite")]
        WEB --> DB
    end
    USER["Recruiter / visitor"] -->|HTTPS| WEB
    GITHUB["GitHub repo"] -->|auto deploy| WEB
```

---

## What's included in this repo

| File | Purpose |
|------|---------|
| [`Dockerfile`](../Dockerfile) | Multi-stage build: Composer + Vite + PHP |
| [`render.yaml`](../render.yaml) | Render Blueprint (one-click infra) |
| [`.env.render.example`](../.env.render.example) | Environment variable template |
| [`docker/render/start.sh`](../docker/render/start.sh) | Container start (migrate cache, `artisan serve`) |
| [`database/seeders/RenderDemoSeeder.php`](../database/seeders/RenderDemoSeeder.php) | Fast demo catalog (~12 products) |

---

## Quick start (Blueprint — recommended)

### 1. Push to GitHub

Ensure the repo is on GitHub (Render connects to Git).

### 2. Create Blueprint on Render

1. Open [dashboard.render.com](https://dashboard.render.com)
2. **New +** → **Blueprint**
3. Connect the **carPart** repository
4. Render reads [`render.yaml`](../render.yaml) and creates **carpart** Web Service

### 3. Set `APP_URL` after the first deploy

1. Open the service → **Environment**
2. Set **`APP_URL`** to your live URL, e.g. `https://carpart-xxxx.onrender.com` (no trailing slash)
3. **Save** → triggers a redeploy

Set **`APP_KEY`** (required). Generate locally:

```bash
php artisan key:generate --show
```

Paste into Render → **Environment** **without quotes**:

```env
APP_KEY=base64:FAhxCPFVYck/It2mhHC7/mhHzSNUmyS06KrNAPqsIcA=
```

If your key is raw base64 only (no `base64:` prefix), either add the prefix or redeploy with the latest `start.sh` (it auto-prefixes).

**Also required:** `APP_URL=https://your-service.onrender.com`

### 4. Wait for deploy

- **Build** (~3–8 min): Composer, `npm run build`, Docker image  
- **Startup**: `migrate` + `RenderDemoSeeder` run from `docker/render/start.sh` (free tier has no pre-deploy command)  
- **Health**: `https://YOUR-URL/up` should return **200** (may take longer on first boot while DB seeds)

### 5. Open the site

Visit your Render URL. A **yellow demo banner** shows login hints when `APP_DEMO_MODE=true`.

---

## Manual setup (Dashboard, no Blueprint)

Use this if you prefer clicking through the UI instead of `render.yaml`.

### Step 1 — Web Service

| Field | Value |
|-------|--------|
| **Type** | Web Service |
| **Runtime** | Docker |
| **Dockerfile path** | `./Dockerfile` |
| **Region** | Oregon (or nearest) |
| **Plan** | Free |
| **Health check** | `/up` |

### Step 2 — Database bootstrap (free tier)

**Free tier does not support Pre-Deploy Command.** Migrations and demo seed run automatically on each container start via [`docker/render/start.sh`](../docker/render/start.sh) when `APP_DEMO_MODE=true`.

Paid plans can optionally add a **Pre-Deploy Command** with the same migrate/seed lines to run before start (faster health checks).

### Step 3 — Environment variables

Copy from [`.env.render.example`](../.env.render.example). Minimum set:

```env
APP_NAME=CarPart
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...          # Generate: php artisan key:generate --show
APP_URL=https://YOUR-SERVICE.onrender.com
APP_DEMO_MODE=true

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
MAIL_MAILER=log
```

### Step 4 — Deploy

Connect branch → **Deploy**. Watch **Logs** for errors.

---

## Demo accounts

Seeded by [`RenderDemoSeeder`](../database/seeders/RenderDemoSeeder.php) on each **container start** when `APP_DEMO_MODE=true` (see `docker/render/start.sh`).

| Role | Email | Password |
|------|--------|----------|
| **Admin** | `admin@carpart.test` | `password` |
| **Customer** | `user@carpart.test` | `password` |

**Admin URL:** `/admin` (after logging in as admin)

> For local development with the **full** ~2,500-product catalog, use Sail:  
> `./vendor/bin/sail artisan migrate:fresh --seed`  
> (runs `CatalogSeeder` — **not** used on Render)

---

## Environment reference

### Demo profile (default in `render.yaml`)

| Variable | Value | Why |
|----------|--------|-----|
| `DB_CONNECTION` | `sqlite` | No external MySQL cost |
| `SESSION_DRIVER` | `database` | Sessions in SQLite (avoids file cookie issues on Render) |
| `SESSION_SECURE_COOKIE` | `true` | Required for HTTPS behind Render proxy |
| `QUEUE_CONNECTION` | `sync` | No background worker |
| `BROADCAST_CONNECTION` | `log` | No Reverb service |
| `APP_DEMO_MODE` | `true` | Shows portfolio banner |

### Upgrade path (production-like)

| Add | Render resource |
|-----|-----------------|
| MySQL | External DB (Railway, Aiven, DO) — set `DB_*` |
| Redis | Render Redis — set `REDIS_URL`, `SESSION_DRIVER=redis` |
| Queues | Background Worker — `php artisan queue:work redis` |
| Reverb | Second Web Service — `php artisan reverb:start` + `VITE_REVERB_*` at build |
| Uploads | S3/R2 — configure `FILESYSTEM_DISK=s3` + disk config |

---

## Build & runtime (how the container works)

```mermaid
sequenceDiagram
    participant R as Render
    participant D as Dockerfile
    participant C as Container

    R->>D: docker build
    D->>D: npm run build (Vite)
    D->>D: composer install --no-dev
    R->>C: start.sh migrate + seed
    C->>C: storage:link, config cache
    C->>C: artisan serve :PORT
```

- **Port:** Render sets `$PORT`; start script binds `0.0.0.0:$PORT`
- **HTTPS:** `TrustProxies` + `URL::forceScheme('https')` in production
- **Assets:** Pre-built into `public/build` during Docker build (no Node at runtime)

---

## Free tier behavior (portfolio)

| Behavior | What visitors see |
|----------|-------------------|
| **Sleep** | After ~15 min idle, first click waits ~30–60s |
| **Redeploy / wake** | SQLite may reset → startup script re-runs migrate + seed |
| **Uploads** | Payment proofs on local disk may **not survive** redeploy/restart |

**Resume tip:** Add to README: *"Live demo may cold-start on free tier."*  
**Backup:** Record a short screen capture for offline viewing.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| **First load very slow** | Normal on free tier: container start runs migrate + seed before `artisan serve` |
| **502 / deploy failed** | Check **Logs** → often missing `APP_KEY` or build error |
| **500 on every page** | Set `APP_KEY` (`base64:…` from `php artisan key:generate --show`); set `APP_URL` to exact Render HTTPS URL; redeploy |
| **500 after env change** | Redeploy (startup runs `optimize:clear` — avoid manual config cache on free tier) |
| **419 on POST /login** | Set `APP_URL=https://…onrender.com`; `APP_KEY=base64:…`; `SESSION_SECURE_COOKIE=true`; `SESSION_DRIVER=database`; redeploy latest code (fixes HTTPS proxy for cookies). Clear site cookies or use incognito. |
| **No CSS / unstyled page** | Redeploy latest `start.sh` (must use Laravel `server.php` so `/build/assets/*` are served as static files). Hard-refresh (Ctrl+Shift+R). |
| **CSS/JS broken** | Build failed — search logs for `npm run build` errors |
| **`/up` unhealthy** | App not listening on `$PORT`; verify Docker deploy succeeded |
| **No products** | Redeploy or restart service (startup runs seeder); or Render Shell: `php artisan db:seed --class=RenderDemoSeeder --force` |
| **Login works, then logout** | `APP_URL` mismatch or session path — confirm HTTPS URL |
| **Admin 403** | Log in as `admin@carpart.test`, not customer |

### Useful Render Shell commands

```bash
php artisan migrate:status
php artisan db:seed --class=RenderDemoSeeder --force
php artisan config:clear
php artisan route:list
```

---

## Local test (Docker, before Render)

```bash
# From project root
docker build -t carpart-render .

docker run --rm -p 10000:10000 \
  -e APP_KEY=base64:$(openssl rand -base64 32) \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e APP_URL=http://localhost:10000 \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  -e SESSION_DRIVER=file \
  -e CACHE_STORE=file \
  -e QUEUE_CONNECTION=sync \
  -e BROADCAST_CONNECTION=log \
  -e APP_DEMO_MODE=true \
  carpart-render
```

Migrate and seed run automatically via `start.sh` when `APP_DEMO_MODE=true`.

Open: http://localhost:10000 (first boot may take ~30s while the DB seeds)

---

## Custom domain (optional)

1. Service → **Settings** → **Custom Domains**
2. Add domain; configure DNS as Render instructs
3. Update `APP_URL=https://yourdomain.com`
4. Redeploy

---

## Checklist before sharing on your portfolio

- [ ] Live URL works (`/up` returns 200)
- [ ] `APP_URL` matches public HTTPS URL
- [ ] Demo banner visible; tested admin + customer login
- [ ] README links to this doc and your live URL
- [ ] Noted cold-start on free tier (one line)
- [ ] Optional: GIF/screenshots in README

---

## Related docs

- [README.md](../README.md) — local Sail setup  
- [.env.render.example](../.env.render.example) — env template  
- [render.yaml](../render.yaml) — Blueprint definition
