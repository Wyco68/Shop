# Agent guide — E-Commerce template

## Before coding

1. Read [skill.md](skill.md) and [projectSpec.md](projectSpec.md).
2. Do **not** add catalog or user seeders to `DatabaseSeeder`.
3. Do **not** hardcode store name, currency, or payment labels — use `config/shop.php` and env vars.

## Conventions

- Match existing Laravel patterns: FormRequests, policies, service layer for transactions.
- Use `UserRole` enum and `$user->isAdmin()` — avoid raw `'admin'` strings in new code.
- Format money with `<x-money :amount="..." />` or `Money::format()`.
- Invalidate `StoreCache` when changing categories or products in admin.

## Admin bootstrap

- First admin only: `php artisan app:init-admin` or `/setup` (middleware `no_admin_yet`).
- Never add `role` to `User::$fillable` or accept `role` from HTTP input on registration.
- **Never** add `/admin/account`, admin password forms, or password-reset APIs for admins.
- Admin password change: `php artisan admin:change-password` only; optional `php artisan admin:env-password-reset` when `ADMIN_RESET_PASSWORD=true`.

## Admin customization routes

- `/admin/settings/branding` — favicon + logo
- `/admin/settings/payments` — payment methods (not separate account UI)
- `/admin/categories` — category icon uploads

## Infrastructure

- Local/production parity: Redis for session, cache, queue; run a queue worker.
- Document new env vars in `.env.example` and README.

## Scope discipline

- Minimal diffs; no automotive-specific copy or seed data.
- Schema changes only when the task explicitly requires a migration.

## Skills path

Cursor skill copy: [.agents/skills/ecommerce-laravel/SKILL.md](.agents/skills/ecommerce-laravel/SKILL.md)
