# VPS Deployment Guide — Bare Server to Running App

End-to-end guide: a fresh Ubuntu VPS → hardened server → the app running at your domain over HTTPS.
Assumes Ubuntu 22.04/24.04 (Debian 12 works identically). Run as **root** only for the first phase;
everything after Phase 1 runs as the `deploy` user.

If you only need the Docker/app-level steps (server is already hardened), skip to
[Phase 6](#phase-6-deploy-the-app).

---

## Phase 0 — Before you start

- A VPS with a public IP (DigitalOcean, Hetzner, Linode, etc.) and root/console access.
- A domain name, with an **A record** pointing its hostname at the VPS IP (needed for automatic HTTPS in
  Phase 6). You can skip this and use the bare IP over HTTP — see the note in Phase 6.
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
sudo ufw allow 2222/tcp        # your SSH port from Phase 2 (use 22 if you didn't change it)
sudo ufw allow 80/tcp          # HTTP (Caddy uses this for the Let's Encrypt challenge + redirect)
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
port = 2222
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
- `APP_URL` — `https://yourdomain.com` (or `http://your.server.ip` if you have no domain yet)
- `SITE_ADDRESS` — `yourdomain.com` (enables automatic HTTPS) or leave `:80` for the bare-IP/HTTP case
- `DB_PASSWORD` — a strong password
- `PUSHER_*` / `VITE_PUSHER_*` if you want real-time notifications, otherwise set
  `BROADCAST_CONNECTION=log`
- Mail settings if you want verification/password-reset emails to actually send (`MAIL_MAILER=log` writes
  to `storage/logs` only, which is fine for getting started)

Build the image:

```bash
docker compose -f docker-compose.vps.yml --env-file .env.vps build
```

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

The `app` container runs migrations and storage setup on boot. `worker` and `scheduler` wait for `app` to
report healthy before starting, so there's no startup race. Once `app` is healthy, visit:

```
https://yourdomain.com/setup       (or http://your.server.ip/setup)
```

to configure the store and create the first administrator.

**No domain yet?** Leave `SITE_ADDRESS=:80` in `.env.vps` — Caddy serves plain HTTP on port 80 with no
TLS. You also need to set `SESSION_SECURE_COOKIE=false` in this case — browsers refuse to store a
`Secure` cookie over plain HTTP, which would otherwise break the `/setup` form's CSRF token and any
login. Point a domain at the server later, then change `SITE_ADDRESS`/`APP_URL` to the domain and
`SESSION_SECURE_COOKIE` back to `true`, and run
`docker compose -f docker-compose.vps.yml --env-file .env.vps up -d` again to pick it up; Caddy will
issue a certificate automatically.

One-off Composer/Artisan commands, run through the container instead of installing anything on the host:

```bash
docker compose -f docker-compose.vps.yml exec app php artisan tinker
docker compose -f docker-compose.vps.yml exec app php artisan admin:change-password
docker compose -f docker-compose.vps.yml run --rm app composer show
```

---

## Phase 7 — Queue worker & scheduler ("supervisor", the Docker-native way)

`docker-compose.vps.yml` already runs the queue worker and Laravel scheduler as their own containers
(`worker` runs `queue:work`, `scheduler` runs `schedule:work`), each with `restart: unless-stopped`. This
*is* the production process supervisor here — Docker's restart policy plays the role `supervisord` plays
in the single-container dev image (`docker/Dockerfile`); there's no separate supervisor process to
install on the host or inside these containers.

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
docker compose -f docker-compose.vps.yml --env-file .env.vps up -d --build
```

This rebuilds the image (new code + any new dependencies) and recreates the containers; migrations run
automatically on the new `app` container's boot. Expect a few seconds of downtime during the swap — this
single-VPS setup doesn't do rolling/zero-downtime deploys.

---

## Phase 11 — Day-to-day operations

```bash
# Logs (all services, or one)
docker compose -f docker-compose.vps.yml logs -f
docker compose -f docker-compose.vps.yml logs -f app

# Resource usage
docker stats

# Disk usage of volumes (mysql-data, redis-data, app-storage, caddy-data)
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
| TLS | Automatic via Caddy + Let's Encrypt, once `SITE_ADDRESS` is a domain (Phase 6) |
| `APP_DEBUG` | `false` in `.env.vps.example` — verify you didn't flip it back |
| `APP_KEY` | Unique, generated for this deployment, not reused from local/other envs (Phase 6) |
| OS security patches | Not automated by this guide — consider `sudo apt install unattended-upgrades` |

Optional hardening not covered above: `unattended-upgrades` for automatic OS security patches, and a
swap file (`fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile`,
then add to `/etc/fstab`) if your VPS has 1–2 GB RAM — MySQL + Redis + PHP can pressure low-memory boxes
under load.
