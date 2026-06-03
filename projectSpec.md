# 1. Overview

- **Purpose:** Reusable e-commerce platform for any product-based business (physical or digital).
- **Target users:** Shoppers and store administrators.
- **Scope:** Monolithic Laravel app — storefront, admin, cart, manual payment checkout, inventory, refunds, notifications.

# 2. Architecture

- **Frontend:** Server-rendered Blade + Tailwind + Alpine.js.
- **Backend:** Laravel 11 services, Eloquent, MySQL.
- **Data flow:** Request → middleware → controller → service (transactions) → MySQL. Events → Pusher → Echo/Alpine.
- **Bootstrap:** Empty migrations only; first admin via `app:init-admin` or `/setup`.

# 3. Features

## Catalog

- Products, categories, optional brands, variants, per-variant inventory.
- `products.compatibility` JSON = optional metadata (not domain-specific).
- Cached category lists and product listing (Redis).

## Checkout

- DB cart → payment method selection → order → transfer instructions → proof upload.
- Duplicate proofs blocked via SHA-256 hash.
- Currency from `config('shop.currency')`.

## Admin

- Dashboard, products, categories, orders, payments, refunds, users, **payment methods**.
- Empty-state hints until catalog and payment methods exist.

# 4. Authentication

- Session-based (Breeze-style).
- Roles: `admin`, `user` (`App\Enums\UserRole`).
- Admin: one-time bootstrap only; no promotion via registration or user UI.

# 5. Database

**Tables:** users, products, categories, brands, product_variants, inventories, inventory_movements, discounts, carts, orders, payments, payment_methods, notifications, coupons, order_status_histories, refund_requests, user_spending.

**Inventory movement types:** `IN`, `OUT`, `RESERVE`, `RELEASE` only.

**Orders:** Snapshots on `order_items`; never use live catalog price after order creation.

# 6. Realtime

- Private channel `user.{id}` for notifications.
- Persisted in DB; Alpine + sessionStorage on client.

# 7. Security

- Policies + `is_admin` middleware.
- Throttle: auth (register), checkout (order + payment).
- Files: public disk for catalog images, private/S3 for proofs.

# 8. Performance

- Eager loading on listings; indexes on `products(is_active, created_at)`, `categories(is_active, slug)`.
- Pagination on all list endpoints.

# 9. Deployment

- Local: Docker Compose or Sail with Redis + queue worker.
- Production: Render Pro (see `docs/DEPLOY-RENDER.md`) — MySQL, Redis, worker, S3, Pusher.

# 10. Out of scope

- Stripe/PayPal automation, multi-tenant marketplace.
