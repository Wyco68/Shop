# CarPart E-Commerce Platform

A production-ready monolithic e-commerce platform built with Laravel 11, featuring robust inventory management, a secure pay-by-transfer flow with payment proof verification, and real-time notifications.

## Core Features
- Product catalog with categories, brands, and variants.
- DB-backed shopping cart.
- Pay-by-transfer checkout with SHA-256 duplicate proof upload detection.
- ACID-compliant inventory management using pessimistic locking to prevent overselling.
- Admin dashboard for manual payment verification, order management, and refund processing.
- Real-time notifications using Laravel Reverb and Alpine.js.

## Tech Stack
- **Backend:** Laravel 11, PHP 8.4
- **Database:** MySQL 8, Redis (Caching, Sessions, Queues)
- **Frontend:** Blade, TailwindCSS v4, Alpine.js
- **Realtime:** Laravel Reverb, Echo, Pusher-js
- **Infrastructure:** Docker (Laravel Sail)

## Setup Instructions

The project uses Docker via Laravel Sail. Run all commands from WSL.

1. **Start the Containers:**
   ```bash
   ./vendor/bin/sail up -d
   ```
2. **Initialize Database:**
   ```bash
   ./vendor/bin/sail artisan migrate:fresh --seed
   ```
3. **Install Frontend Assets:**
   ```bash
   npm install
   npm run build
   ```

## Environment Variables
- `APP_URL`: Application URL.
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: MySQL connection settings.
- `REDIS_HOST`, `REDIS_PORT`: Redis connection for queues and caching.
- `BROADCAST_CONNECTION=reverb`: Set to Reverb for WebSocket events.
- `FILESYSTEM_DISK=local`: Storage disk for payment proofs.

## Development Commands
- **Test Suite:** `./vendor/bin/sail test`
- **Application Shell:** `./vendor/bin/sail shell`
- **View Logs:** `./vendor/bin/sail logs -f`
- **Stop Environment:** `./vendor/bin/sail stop`

## Security Considerations
- **Authentication:** Standard Laravel session-based authentication.
- **Authorization:** `is_admin` middleware for admin routes, Eloquent Policies for resource access.
- **Race Conditions:** `lockForUpdate()` is used on inventory rows to prevent overselling.
- **File Uploads:** Payment proofs are validated and hashed (SHA-256) to block duplicates.

## Deploy on Render (portfolio demo)

Host a **free portfolio demo** on Render (Docker, SQLite, lightweight seed):

1. Push to GitHub → [Render Blueprint](https://dashboard.render.com) → connect repo (`render.yaml`).
2. Set **`APP_URL`** to your `https://….onrender.com` URL after the first deploy.
3. Open the site — demo logins appear in the top banner.

**Full guide:** [docs/HOSTING-RENDER.md](docs/HOSTING-RENDER.md)

| Demo login | Email | Password |
|------------|--------|----------|
| Admin | `admin@carpart.test` | `password` |
| Customer | `user@carpart.test` | `password` |

> Free tier sleeps when idle; first visit may take ~30–60s. Local full catalog: `./vendor/bin/sail artisan migrate:fresh --seed`.
