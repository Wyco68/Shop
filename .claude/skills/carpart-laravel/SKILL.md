---
name: carparts-laravel
description: >-
  Implements features for a Laravel car parts e-commerce system using an existing
  database schema with products, variants, inventory, orders, payments, and discounts.
  Focuses on correct relationships, inventory movements, filtering, and order lifecycle.
---

# System Context

You are working on a Laravel application with an EXISTING database schema.

DO NOT modify table structure unless explicitly required.

You MUST use the existing tables and relationships exactly as defined.

---

# Core Tables (USE EXACTLY)

## Products Domain

- products
- product_variants
- brands
- categories

### Rules

- products is the base entity
- product_variants is the sellable unit
- price can come from:
  - product.base_price
  - OR variant.price_override (if not null)

---

## Inventory Domain

- inventories (linked to variant_id)
- inventory_movements

### Movement Types (STRICT)

ONLY use:

- RESERVE
- OUT
- IN

DO NOT invent new types.

---

## Orders Domain

- orders
- order_items

### Rules

- order_items stores snapshot:
  - product_name_snapshot
  - sku_snapshot
  - unit_price
  - final_price

NEVER rely on live product price after order is created.

---

## Cart Domain

- carts
- cart_items

Supports:
- user_id OR session_id

---

## Payments

- payments linked to orders

Supports:
- provider
- proof upload
- proof_hash (anti-duplicate)

---

## Users

- users table includes:
  - role (admin/user)
  - phone_num
  - address

IMPORTANT:
- phone_num and address are optional in updates

---

## Discounts

- discounts can apply to:
  - product_id OR category_id
- supports:
  - type (percentage/fixed)
  - min_quantity
  - date range

---

# Business Logic (CRITICAL)

## 1. Add to Cart

- Resolve variant_id
- Store in cart_items:
  - variant_id
  - quantity

---

## 2. Pricing Logic

When calculating price:

1. Start with:
   - variant.price_override ?? product.base_price

2. Apply discount if:
   - active
   - quantity >= min_quantity

3. Store in order_items:
   - unit_price
   - discount_amount
   - final_price

---

## 3. Order Creation

Steps:

1. Validate stock:
   - inventories.stock_quantity - reserved_quantity >= quantity

2. Create order

3. Create order_items (snapshot data)

4. Create inventory movement:
   - type: RESERVE
   - increase reserved_quantity

---

## 4. Order Confirmation

- Create movement:
  - type: OUT
- Update:
  - stock_quantity -= quantity
  - reserved_quantity -= quantity

---

## 5. Order Cancellation

- Release reserved stock:
  - reserved_quantity -= quantity
- NO OUT movement

---

## 6. Inventory Adjust (Admin)

- Create movement:
  - type: IN
- Update stock_quantity

---

## 7. Inventory Movement Integrity

Order MUST be:

1. RESERVE
2. OUT

This matches tests.

---

# Filtering System

Products must support:

- category (products.category_id)
- brand (products.brand_id)
- made_in (products.made_in OR brands.country)
- price range (computed from variant/product)

Example:

```http
/products?category_id=1&brand_id=2&min_price=50&max_price=200

Query Rules
Always join:
products
variants
inventories (if needed)
Avoid N+1:
use eager loading
API / Controller Behavior
Product Listing
return:
product info
lowest variant price
brand
category
Product Detail
include:
variants
stock info
Cart
must support:
guest (session_id)
user (user_id)
Profile Update

CRITICAL:

phone_num → nullable
address → nullable

DO NOT require them if unchanged.

Constraints
DO NOT change schema
DO NOT modify tests
DO NOT bypass inventory logic
DO NOT hardcode values
Success Criteria
Cart works
Orders correctly reserve and deduct stock
Inventory movements correct order
Filtering works
Profile update passes validation tests

# Failures (STRICT)

## Schema
- DO NOT modify tables, columns, relationships
- DO NOT move inventory from variant → product

## Inventory
- MUST follow: RESERVE → OUT
- DO NOT skip reserve
- DO NOT deduct stock directly
- DO NOT allow negative stock
- MUST update reserved_quantity correctly

## Movement Types
ONLY:
- RESERVE, OUT, IN
NO custom values

## Pricing
- Use: variant.price_override ?? product.base_price
- DO NOT recalculate after order
- DO NOT use live product price in orders

## Orders
- MUST store snapshot fields
- DO NOT depend on product table after creation

## Cart
- MUST use variant_id (NOT product_id)

## Filtering
- MUST consider variants for price
- DO NOT hardcode filters

## Profile
- phone_num, address = optional
- DO NOT reset email_verified_at if email unchanged

## Performance
- NO N+1 queries
- USE eager loading

## Authorization
- MUST use AuthorizesRequests trait
- DO NOT remove authorization

## Discounts
- Apply ONLY if active, valid date, min_quantity met

## Payments
- NO duplicate proof_hash
- DO NOT mark paid without validation

## Tests
- DO NOT modify tests
- DO NOT bypass logic

---

# Failure = invalid implementation