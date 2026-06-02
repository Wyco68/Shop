# Upstash Redis + Pusher (Render)

Use **Upstash** for sessions/cache and **Pusher Channels** for realtime notifications while the Laravel app stays on **Render**.

```mermaid
flowchart LR
  Browser["Browser"]
  Render["Render — Laravel"]
  Upstash["Upstash Redis"]
  Pusher["Pusher Channels"]

  Browser -->|HTTPS| Render
  Browser -->|WSS| Pusher
  Render -->|REDIS_URL| Upstash
  Render -->|broadcast API| Pusher
```

---

## 1. Upstash Redis

1. Sign up at [console.upstash.com](https://console.upstash.com).
2. **Create database** → region near Render (e.g. `us-west-1`).
3. Copy the **Redis URL** (`rediss://…`).

In Render **Environment**:

```env
REDIS_CLIENT=predis
REDIS_URL=rediss://default:xxxx@xxxx.upstash.io:6379
SESSION_DRIVER=redis
CACHE_STORE=redis
```

Free tier: ~256 MB, ~500K commands/month. Idle DB may archive after ~14 days.

---

## 2. Pusher Channels

1. Sign up at [dashboard.pusher.com](https://dashboard.pusher.com).
2. Create a **Channels** app (sandbox is free).
3. Note **app_id**, **key**, **secret**, **cluster** (e.g. `mt1`).

In Render **Environment**:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=mt1
PUSHER_SCHEME=https
PUSHER_PORT=443

VITE_PUSHER_APP_KEY=...          # same as PUSHER_APP_KEY
VITE_PUSHER_APP_CLUSTER=mt1        # same as cluster
VITE_PUSHER_SCHEME=https
```

**Important:** `VITE_*` values are embedded at **Docker build** time. After setting Pusher env vars, trigger a **new deploy** (clear build cache if needed).

### Pusher app settings

- Enable **client events** only if you need them (not required for this app).
- Under **App Keys**, use the same cluster as `PUSHER_APP_CLUSTER`.

---

## 3. Local development (Sail)

In `.env` (Sail Redis for local; Pusher for realtime):

```env
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
SESSION_DRIVER=redis
CACHE_STORE=redis
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_PUSHER_SCHEME=https
```

Then:

```bash
npm run build   # or npm run dev
./vendor/bin/sail up -d
```

---

## 4. Verify realtime

1. Log in as **admin** in one browser, **customer** in another (or incognito).
2. Trigger a notification (e.g. admin updates an order status).
3. Customer should see the badge update without refresh (Echo + Pusher).

If not working:

| Check | Action |
|-------|--------|
| No Echo | DevTools → Network: `app-*.js` 200; env `VITE_PUSHER_APP_KEY` set before build |
| Pusher debug | Pusher dashboard → **Debug Console** → see connections/events |
| 403 on `/broadcasting/auth` | User must be logged in; CSRF/session OK |
| Wrong channel | App uses private channel `user.{id}`, event `.NotificationCreated` |

---

## 5. Render checklist

- [ ] `REDIS_URL` from Upstash (with `rediss://`)
- [ ] `REDIS_CLIENT=predis`
- [ ] All `PUSHER_*` and `VITE_PUSHER_*` set
- [ ] `APP_URL` = your `https://….onrender.com`
- [ ] `APP_KEY=base64:…` (no quotes)
- [ ] Redeploy after changing `VITE_*`

See also: [HOSTING-RENDER.md](HOSTING-RENDER.md)
