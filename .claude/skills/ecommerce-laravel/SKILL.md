---
name: ecommerce-laravel
description: >-
  Implements features for a generic Laravel e-commerce template with products,
  variants, inventory, orders, manual payments, and admin bootstrap. Uses existing
  schema; no default seed data.
---

# E-Commerce Laravel

Read [skill.md](/skill.md) at the repository root for full domain rules.

## Quick rules

- Existing schema — avoid migrations unless required.
- No seeders for catalog/users; first admin per README Admin Setup.
- Payment methods via admin CRUD before checkout works.
- Inventory: `IN`, `OUT`, `RESERVE`, `RELEASE` only.
- Orders snapshot line items; use `config('shop.currency')`.
