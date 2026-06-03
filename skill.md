# E-Commerce Laravel — Agent Skill

## Context

Laravel 11 monolith with an **existing schema**. Do not change migrations unless explicitly required.

**No default seed data.** After `migrate`, use `php artisan app:init-admin` or `/setup` once. Store data is created in admin.

## Configuration

- `config('shop.name')` — `STORE_NAME` / `APP_NAME`
- `config('shop.currency')` — `CURRENCY`
- `config('shop.currency_symbol')` — display via `<x-money>` or `App\Support\Money::format()`
- Disks: `config('filesystems.product_disk')`, `config('filesystems.private_disk')`

## Core tables

| Domain | Tables |
|--------|--------|
| Catalog | products, product_variants, categories, brands |
| Inventory | inventories, inventory_movements |
| Cart | carts, cart_items |
| Orders | orders, order_items, order_status_histories |
| Payments | payments, payment_methods |
| Promotions | discounts, coupons |
| Users | users (role: admin/user), user_spending, refund_requests, notifications |
| Settings | store_settings (branding), admin_password_change_logs |

## Admin customization

- **Branding:** `store_settings` (singleton), `/admin/settings/branding`, cache key `cache:settings`.
- **Category icons:** `categories.icon_path`, upload to `categories/` on public/product disk.
- **Payments:** `/admin/settings/payments` — `type` (bank|mobile|crypto), `config` encrypted JSON, `is_active` = enabled.
- **Admin passwords:** CLI only — `php artisan admin:change-password`. Never add `/admin/account` or password APIs for admins.

## Rules

1. **Sellable unit** = `product_variants`; stock on `inventories.variant_id`.
2. **Price** = `product.base_price` or `variant.price_override` when set.
3. **Order items** snapshot name, SKU, prices — never recalculate from catalog after order.
4. **Inventory movements** only: `IN`, `OUT`, `RESERVE`, `RELEASE`.
5. **Checkout** requires active `payment_methods` (admin CRUD).
6. **Admin** cannot be created via registration or user management UI.
7. **Cache keys** — use `App\Support\StoreCache`; call `forgetSettings()` on branding updates and `forgetCategories()` on category/icon changes.
8. **Uploads** — `App\Services\SecureUploadService` only; never store executable extensions.

## Services

- `CartService`, `OrderService`, `InventoryService`, `PaymentService`, `DiscountService`, `AdminBootstrapService`, `AdminPasswordService`, `StoreSettingsService`, `SecureUploadService`

## Testing

Use factories (`User::factory()->admin()`, `PaymentMethod::factory()`, etc.). Do not rely on archived seeders in `database/seeders/archive/`.
