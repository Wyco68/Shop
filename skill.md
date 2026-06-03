# E-Commerce Laravel — Agent Skill

## Configuration

| Key | Source |
|-----|--------|
| `config('shop.name')` | `STORE_NAME` / `APP_NAME` |
| `config('shop.currency')` | `CURRENCY` |
| `config('shop.currency_symbol')` | `<x-money>` or `Money::format()` |
| Product uploads | `config('filesystems.product_disk')` |
| Payment proofs | `config('filesystems.private_disk')` |

## Core tables

| Domain | Tables |
|--------|--------|
| Catalog | products, product_variants, categories, brands |
| Inventory | inventories, inventory_movements |
| Cart | carts, cart_items |
| Orders | orders, order_items, order_status_histories |
| Payments | payments, payment_methods |
| Promotions | discounts, coupons |
| Users | users, user_spending, refund_requests, notifications |
| Settings | store_settings, admin_password_change_logs |

## Rules

1. **Sellable unit** = `product_variants`; stock on `inventories.variant_id`.
2. **Price** = `product.base_price` or `variant.price_override` when set.
3. **Order items** snapshot name, SKU, prices — never recalculate from catalog after order.
4. **Inventory movements** only: `IN`, `OUT`, `RESERVE`, `RELEASE`.
5. **Checkout** requires active `payment_methods` (admin CRUD).
6. **Admin** cannot be created via registration or user management UI.
7. **Cache** — use `App\Support\StoreCache`; `forgetSettings()` on branding, `forgetCategories()` on category/icon changes.
8. **Uploads** — `App\Services\SecureUploadService` only; no executable extensions.
9. **Schema** — do not change migrations unless the task requires it.

## Services

`CartService`, `OrderService`, `InventoryService`, `PaymentService`, `DiscountService`, `AdminBootstrapService`, `AdminPasswordService`, `StoreSettingsService`, `SecureUploadService`

## Testing

Use factories (`User::factory()->admin()`, `PaymentMethod::factory()`, etc.). Do not use archived seeders in `database/seeders/archive/`.
