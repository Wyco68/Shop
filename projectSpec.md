# Overview

- Reusable e-commerce platform for physical or digital products.
- Users: shoppers and store administrators.
- Monolithic Laravel app: storefront, admin, cart, manual payment checkout, inventory, refunds, notifications.

# Architecture

- **UI:** Blade + Tailwind + Alpine.js.
- **Backend:** Laravel 11 services, Eloquent, MySQL.
- **Flow:** Request → middleware → controller → service (transactions) → MySQL. Events → Pusher → Echo/Alpine.
- **Bootstrap:** Migrations only; first admin via README Admin Setup.

# Features

## Catalog

- Products, categories, optional brands, variants, per-variant inventory.
- `products.compatibility` JSON = optional metadata.
- Cached category lists and product listing (Redis).

## Checkout

- DB cart → payment method → order → transfer instructions → proof upload.
- Duplicate proofs blocked (SHA-256 hash).
- Currency from `config('shop.currency')`.

## Admin

- Dashboard, products, categories, orders, payments, refunds, users, payment methods.
- Shop name: set once at `/setup` (not editable in admin)
- Branding: `/admin/settings/branding` (favicon, logo)
- Payment methods: `/admin/settings/payments`
- Category icons: `/admin/categories`
- Empty-state hints until catalog and payment methods exist.

# Authentication

- Session-based (Breeze-style).
- Roles: `admin`, `user` (`App\Enums\UserRole`).
- Admin: one-time bootstrap only; no promotion via registration or user UI.

# Database

**Tables:** users, products, categories, brands, product_variants, inventories, inventory_movements, discounts, carts, orders, payments, payment_methods, notifications, coupons, order_status_histories, refund_requests, user_spending.

**Inventory movements:** `IN`, `OUT`, `RESERVE`, `RELEASE` only.

**Orders:** Snapshots on `order_items`; never use live catalog price after order creation.

# Realtime

- Private channel `user.{id}` for notifications.
- Persisted in DB; Alpine + sessionStorage on client.
- Read notifications are deleted after 10 days (`notifications:prune-read`, scheduled daily).

# Security

- Policies + `is_admin` middleware.
- Throttle: auth (register), checkout (order + payment).
- Public disk for catalog images; private/S3 for payment proofs.

# Performance

- Eager loading on listings; indexes on `products(is_active, created_at)`, `categories(is_active, slug)`.
- Pagination on all list endpoints.

# Deployment

- Production: see README → Deployment.

# Out of scope

- Stripe/PayPal automation, multi-tenant marketplace.
