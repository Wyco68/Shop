# VPS Deployment Guide — Bare Server to Running App

End-to-end guide: a fresh Ubuntu VPS → hardened server → the app running at your domain over HTTPS.
Assumes Ubuntu 22.04/24.04 (Debian 12 works identically). Run as **root** only for the first phase;
everything after Phase 1 runs as the `deploy` user.

If you only need the Docker/app-level steps (server is already hardened), skip to
[Phase 6](#phase-6-deploy-the-app).

---

## Phase 0 — Before you start

- A VPS with a public IP (DigitalOcean, Hetzner, Linode, etc.) and root/console access.
- A domain name with its DNS record pointing at the VPS IP. TLS is handled by Nginx (running on the
  host, not in Docker) + certbot's HTTP-01 challenge (Phase 6) — if the domain is proxied through
  Cloudflare (orange cloud), switch it to "DNS only" (grey cloud) before issuing the certificate so
  Let's Encrypt can reach the origin directly; you can switch it back to proxied afterward.
- Your local machine's SSH public key (`~/.ssh/id_ed25519.pub` or similar). Generate one if you don't
  have it: `ssh-keygen -t ed25519`.

---

## Phase 1 — Non-root user

SSH in as root (or via your provider's web console) and create the deploy user:

```bash
adduser deploy
usermod -aG sudo deploy
```

Copy your SSH key to the new user so you can log in without a password:

```bash
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy
```

**Before closing this session**, open a *second* terminal and confirm you can log in as `deploy`:

```bash
ssh deploy@your.server.ip
```

Don't proceed until that works — you still have the root session as a fallback if it doesn't.

---

## Phase 2 — SSH hardening

From here on, run commands as `deploy` with `sudo`.

Edit `/etc/ssh/sshd_config`:

```bash
sudo nano /etc/ssh/sshd_config
```

Set:

```
Port 2222
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
```

`Port 2222` is optional (any unused port above 1024) — it only reduces drive-by bot noise, it is not a
real security boundary. Skip it and keep `Port 22` if you'd rather not deal with the extra firewall rule
below.

Apply, but **do not close your current session yet**:

```bash
sudo systemctl restart ssh
```

Open a *new* terminal and confirm you can still connect with the new port before disconnecting the old
session:

```bash
ssh -p 2222 deploy@your.server.ip
```

If you get locked out, your original session (if still open) is your way back in — fix `sshd_config` and
restart `ssh` again from there.

---

## Phase 3 — Firewall (UFW)

```bash
sudo ufw allow 22/tcp        # your SSH port from Phase 2 (use 22 if you didn't change it)
sudo ufw allow 80/tcp          # HTTP (Nginx uses this for the Let's Encrypt challenge + redirect)
sudo ufw allow 443/tcp         # HTTPS
sudo ufw enable
sudo ufw status verbose
```

Do **not** open 3306 (MySQL) or 6379 (Redis) — the Docker stack keeps both on an internal Docker network
with no host port binding, so they're unreachable from outside by default. Leave it that way.

If your provider also has a cloud-level firewall (DigitalOcean Cloud Firewall, AWS Security Group, etc.),
mirror the same three rules there — UFW alone won't help if the cloud firewall blocks the port first.

---

## Phase 4 — fail2ban

```bash
sudo apt update
sudo apt install -y fail2ban
sudo tee /etc/fail2ban/jail.local > /dev/null <<'EOF'
[sshd]
enabled = true
port = 22
maxretry = 4
bantime = 1h
findtime = 10m
EOF
sudo systemctl enable --now fail2ban
sudo fail2ban-client status sshd
```

Set `port` to match whatever you chose in Phase 2 (`22` if you didn't change it).

---

## Phase 5 — Docker Engine

Install Docker from the official repository (the Ubuntu repo's `docker.io` package lags behind):

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

Let `deploy` run Docker without `sudo`:

```bash
sudo usermod -aG docker deploy
```

Log out and back in (group membership only applies to new sessions), then verify:

```bash
docker run --rm hello-world
docker compose version
```

**No PHP, Composer, or Sail install needed on the server.** The production `Dockerfile`
([docker/vps/Dockerfile](docker/vps/Dockerfile)) builds the Composer `vendor/` directory and the compiled
frontend assets *into the image* at build time — the VPS only ever needs Docker. Sail
(`./vendor/bin/sail`) is a local-development convenience tool used on your own machine; it has no role
here. If you ever need a one-off Composer or Artisan command on the server, run it through the running
container instead (see Phase 6).

---

## Phase 6 — Deploy the app

Clone the repo as `deploy`:

```bash
cd ~
git clone <repo-url> carPart
cd carPart
```

Create the production env file:

```bash
cp .env.vps.example .env.vps
nano .env.vps
```

At minimum, set:
- `APP_URL` — `https://yourdomain.com`
- `DB_PASSWORD` — a strong password
- `REVERB_APP_ID`/`REVERB_APP_KEY`/`REVERB_APP_SECRET` (random values, e.g. `openssl rand -hex 16`)
  and `VITE_REVERB_APP_KEY`/`VITE_REVERB_HOST` if you want real-time notifications — see the
  comments in `.env.vps.example`; otherwise set `BROADCAST_CONNECTION=log`. The Nginx config below
  already proxies the WebSocket path the browser needs (`/app/`).
- Mail settings if you want verification/password-reset emails to actually send (`MAIL_MAILER=log` writes
  to `storage/logs` only, which is fine for getting started)

```bash
echo "REVERB_APP_ID=$(openssl rand -hex 8)"
echo "REVERB_APP_KEY=$(openssl rand -hex 16)"
echo "REVERB_APP_SECRET=$(openssl rand -hex 32)"

```
Copy the three printed lines straight into .env.vps. Then set VITE_REVERB_APP_KEY to the same value as REVERB_APP_KEY (the client and server must agree on this key — it's not a separate secret, it's how Reverb identifies which "app" the connection belongs to):

nano .env.vps

Note VITE_REVERB_APP_KEY and REVERB_APP_KEY are identical above — that's intentional, not a typo. 

Install Nginx and certbot, then set up the reverse proxy site (TLS isn't handled by Docker — the
`app` container only publishes `127.0.0.1:8080`, which Nginx proxies into):

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
sudo cp docker/vps/nginx.conf.example /etc/nginx/sites-available/yourdomain.com
sudo nano /etc/nginx/sites-available/yourdomain.com   # replace yourdomain.com with your real domain
sudo ln -s /etc/nginx/sites-available/yourdomain.com /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

Issue the certificate — this only needs Nginx answering on port 80, not the app container, so it's safe
to run before the stack is up:

```bash
sudo certbot --nginx -d yourdomain.com
```

certbot edits `/etc/nginx/sites-available/yourdomain.com` in place to add the `ssl_certificate` lines
and an HTTP→HTTPS redirect, then reloads Nginx. It also installs a systemd timer that renews
automatically before the 90-day expiry and reloads Nginx itself — no extra hook needed. Verify the
renewal path works without consuming a real renewal:

```bash
sudo certbot renew --dry-run
```

If `tmux` isn't installed: `sudo apt install -y tmux`. Reattach after a dropped connection with
`tmux attach -t build`; detach intentionally with `Ctrl+b` then `d`.

If the VPS has 2 GB RAM or less, add swap first — the PHP extension compile step alone can spike
memory usage:

```bash
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Build the image. This compiles PHP extensions and runs the frontend build — it can take several
minutes and will get killed if your SSH session drops, so run it inside `tmux` (or `screen`):

```bash
tmux new -s build
docker compose -f docker-compose.vps.yml --env-file .env.vps build app
```

`worker` and `scheduler` reuse this same image (no `build:` of their own in
[docker-compose.vps.yml](docker-compose.vps.yml)) — only `app` needs building. If you instead pass no
service name (or run `up -d --build`) on a compose file where multiple services share a build config,
Compose can build them all in parallel, which will starve a small VPS of CPU/RAM during the PHP
extension compile; building `app` alone avoids that.


Generate a production `APP_KEY` (never reuse one from local dev or another deployment):

```bash
docker compose -f docker-compose.vps.yml --env-file .env.vps run --rm app php artisan key:generate --show
```

Paste the printed `base64:...` value into `APP_KEY=` in `.env.vps`, then start the stack:

```bash
docker compose -f docker-compose.vps.yml --env-file .env.vps up -d
```

Watch it come up:

```bash
docker compose -f docker-compose.vps.yml logs -f
```

The `app` container runs migrations and storage setup on boot. `worker`, `scheduler`, and `reverb` wait
for `app` to report healthy before starting, so there's no startup race. Once `app` is healthy, configure
the store and create the first administrator — this is CLI-only (there is no `/setup` web page) so it
can't be raced or probed by anyone hitting the domain before you finish:

```bash
docker compose -f docker-compose.vps.yml exec app php artisan store:setup
```

Answer the prompts (store name, currency, administrator email/password). Until this command has been run,
every web request returns a 503 ("This store has not been configured yet"), so it's safe to leave the
domain pointed at the server while you do this.

### Restrict trusted proxies (required — do this before going live)

[bootstrap/app.php](bootstrap/app.php) ships with `trustProxies(at: '*')`, which is a development-only
placeholder: it tells Laravel to trust `X-Forwarded-For`/`X-Forwarded-Proto` from *any* client, not just
your reverse proxy. On a real VPS this is exploitable — a visitor can set their own `X-Forwarded-For`
header on every request and `$request->ip()` will return whatever they sent. Several security controls
key off that value, so trusting `'*'` lets an attacker bypass them outright:

- Login, password-reset, and email-resend rate limiting (`app/Providers/AppServiceProvider.php`) — an
  attacker can rotate a fake IP on every request and brute-force a password with no lockout.
- `AuthSecurityLogger` audit entries — the attacker IP recorded for failed logins, lockouts, etc. can be
  forged.

In this stack, Nginx runs on the **host** and is the only thing allowed to talk to the `app` container
(it's published as `127.0.0.1:8080`, not exposed publicly — see `docker-compose.vps.yml`). So the only
proxy that should ever be trusted is Nginx itself, as seen from inside the container — which, because of
Docker's port-publishing NAT, is the Docker bridge network's gateway address, not `127.0.0.1`. Find it
after the stack is up:

```bash
docker network inspect carpart_default --format '{{(index .IPAM.Config 0).Gateway}}'
```

(replace `carpart_default` with whatever `docker network ls` shows for this project — it's derived from
the directory name you cloned into). Then edit `bootstrap/app.php` on **this server** and replace the
wildcard with that address:

```php
$middleware->trustProxies(
    at: '172.18.0.1', // <- the Gateway IP printed above, specific to this VPS
    headers: Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO,
);
```

Rebuild and redeploy (`docker compose -f docker-compose.vps.yml --env-file .env.vps build app && docker
compose -f docker-compose.vps.yml --env-file .env.vps up -d`) after editing. The gateway address is
generally stable for a given Compose project but can change if the network is removed and recreated
(e.g. `docker compose down` followed by a fresh `up`) — re-run the `docker network inspect` command and
update `bootstrap/app.php` again if logins start failing to record the right IP after such an event.

One-off Composer/Artisan commands, run through the container instead of installing anything on the host:

```bash
docker compose -f docker-compose.vps.yml exec app php artisan tinker
docker compose -f docker-compose.vps.yml exec app php artisan admin:change-password
docker compose -f docker-compose.vps.yml run --rm app composer show
```

---

## Phase 7 — Queue worker & scheduler ("supervisor", the Docker-native way)

`docker-compose.vps.yml` already runs the queue worker, Laravel scheduler, and Reverb server as their
own containers (`worker` runs `queue:work`, `scheduler` runs `schedule:work`, `reverb` runs
`reverb:start`), each with `restart: unless-stopped`. This *is* the production process supervisor here
— Docker's restart policy plays the role `supervisord` plays in the single-container dev image
(`docker/Dockerfile`); there's no separate supervisor process to install on the host or inside these
containers.

Check worker health and logs:

```bash
docker compose -f docker-compose.vps.yml ps
docker compose -f docker-compose.vps.yml logs -f worker
```

If queued jobs back up under load, scale workers horizontally:

```bash
docker compose -f docker-compose.vps.yml --env-file .env.vps up -d --scale worker=3
```

---

## Phase 8 — Survive a reboot

Docker's systemd service is enabled by default on install; confirm it:

```bash
sudo systemctl is-enabled docker
```

Because every service in `docker-compose.vps.yml` has `restart: unless-stopped`, all containers come back
automatically when Docker restarts after a host reboot — no extra cron or systemd unit needed.

---

## Phase 9 — Backups

Two things need backing up: the MySQL data and user-uploaded files (the `app-storage` volume).

```bash
mkdir -p ~/backups
```

A simple nightly dump + upload archive, as a cron job for `deploy`:

```bash
cat > ~/backup.sh <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
cd ~/carPart
STAMP=$(date +%F)
docker compose -f docker-compose.vps.yml exec -T mysql \
  mysqldump -uroot -p"$(grep ^DB_PASSWORD= .env.vps | cut -d= -f2-)" ecommerce \
  > ~/backups/db-$STAMP.sql
docker compose -f docker-compose.vps.yml exec -T app \
  tar czf - -C /var/www/html/storage/app . > ~/backups/storage-$STAMP.tar.gz
find ~/backups -mtime +14 -delete
EOF
chmod +x ~/backup.sh
crontab -l 2>/dev/null | { cat; echo "0 3 * * * /home/deploy/backup.sh"; } | crontab -
```

This keeps 14 days locally. **Copy `~/backups` off the server regularly** (e.g. `rsync` to another
machine, or push to S3/Backblaze) — a backup that lives only on the server you're backing up doesn't
survive that server dying.

---

## Phase 10 — Updating the app

```bash
cd ~/carPart
git pull
tmux new -s build   # same reasoning as Phase 6 — this can take a few minutes
docker compose -f docker-compose.vps.yml --env-file .env.vps build app
docker compose -f docker-compose.vps.yml --env-file .env.vps up -d
```

Rebuilding `app` and re-running `up -d` recreates all four containers — `worker`/`scheduler`/`reverb`
pick up the new image automatically since they reference `app`'s image tag rather than building their own.
Migrations run automatically on the new `app` container's boot. Expect a few seconds of downtime during
the swap — this single-VPS setup doesn't do rolling/zero-downtime deploys.

---

## Phase 11 — Day-to-day operations

```bash
# Logs (all services, or one)
docker compose -f docker-compose.vps.yml logs -f
docker compose -f docker-compose.vps.yml logs -f app

# Resource usage
docker stats

# Disk usage of volumes (mysql-data, redis-data, app-storage)
docker system df -v

# Shell into the app container
docker compose -f docker-compose.vps.yml exec app bash
```

---

## Phase 12 — Security checklist recap

| Item | Status after this guide |
|------|--------------------------|
| Root SSH login | Disabled (Phase 2) |
| Password SSH auth | Disabled, key-only (Phase 2) |
| Firewall | Only SSH/80/443 open; DB/Redis never exposed (Phase 3) |
| Brute-force protection | fail2ban on sshd (Phase 4) |
| TLS | Host Nginx + certbot (HTTP-01, nginx plugin), auto-renews and reloads Nginx itself (Phase 6) |
| `APP_DEBUG` | `false` in `.env.vps.example` — verify you didn't flip it back |
| `APP_KEY` | Unique, generated for this deployment, not reused from local/other envs (Phase 6) |
| Trusted proxies | `bootstrap/app.php` `trustProxies(at: ...)` set to the Docker gateway IP, not `'*'` — verify this was changed from the placeholder (Phase 6) |
| Store setup | CLI-only (`php artisan store:setup`) — there is no `/setup` web page to probe or race (Phase 6) |
| OS security patches | Not automated by this guide — consider `sudo apt install unattended-upgrades` |

Optional hardening not covered above: `unattended-upgrades` for automatic OS security patches, and a
swap file (`fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile`,
then add to `/etc/fstab`) if your VPS has 1–2 GB RAM — MySQL + Redis + PHP can pressure low-memory boxes
under load.
