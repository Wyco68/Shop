# Agent guide

## Before coding

1. Read [skill.md](skill.md) and [projectSpec.md](projectSpec.md).
2. Do not add catalog or user seeders to `DatabaseSeeder`.
3. Do not hardcode store name, currency, or payment labels — use `config/shop.php` and env vars.

## Conventions

- Match existing Laravel patterns: FormRequests, policies, service layer for transactions.
- Use `UserRole` enum and `$user->isAdmin()` — avoid raw `'admin'` strings in new code.
- Format money with `<x-money :amount="..." />` or `Money::format()`.
- Invalidate `StoreCache` when changing categories or products in admin.
- Document new env vars in `.env.example` and README.

## Admin and auth rules

- First admin: follow README Admin Setup; never add `role` to `User::$fillable` or accept `role` from HTTP on registration.
- Never add `/admin/account`, admin password forms, or password-reset APIs for admins.
- Admin passwords: `php artisan admin:change-password` or `admin:env-password-reset` when `ADMIN_RESET_PASSWORD=true`.

## Implementation principles

- Controllers handle HTTP only; validation in FormRequests; business logic in services.
- Inventory changes only via `InventoryService` with `lockForUpdate()` inside transactions.
- Payment proofs: `PaymentService` SHA-256 hash prevents duplicate receipt reuse.
- Admin routes: `auth` + `is_admin`, grouped under `prefix('admin')`.
- User actions: policies (e.g. `OrderPolicy`) — do not rely on UI hiding alone.

## Scope

- Minimal diffs; no domain-specific copy or seed data.
- Schema changes only when the task explicitly requires a migration.

## Skills path

Cursor skill: [.agents/skills/ecommerce-laravel/SKILL.md](.agents/skills/ecommerce-laravel/SKILL.md)
