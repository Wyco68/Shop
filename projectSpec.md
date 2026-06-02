# 1. Overview
- **Purpose:** A scalable e-commerce system built on Laravel to handle physical/digital products with a pay-by-transfer flow.
- **Target users:** Customers buying car parts and admins managing catalog, orders, and refunds.
- **System scope:** Monolithic web application including storefront, admin dashboard, cart, checkout, and inventory tracking.

# 2. Architecture
- **Frontend structure:** Server-Side Rendered (SSR) Blade templates integrated with TailwindCSS v4 and Alpine.js for interactivity.
- **Backend:** Laravel 11 monolith handling routing, business logic (Services), and database interaction. No Supabase is used; native MySQL with Eloquent ORM is implemented.
- **Data flow:** Request → Middleware (Auth/Admin) → Controller → Service Layer (ACID Transactions) → MySQL. Realtime events flow from Backend → Pusher Channels → Echo/Alpine.js.

# 3. Features (DETAILED)
- **Product & Inventory Management:** 
  - Description: Products have variants. Inventory is tracked per variant.
  - User flow: Admin creates products/variants and adds inventory. Customers browse and add to cart.
  - Edge cases: High-concurrency purchases.
  - Constraints: Enforced via `lockForUpdate()` DB transactions to prevent negative inventory.
- **Checkout & Payment:** 
  - Description: Pay-by-transfer flow requiring payment proof.
  - User flow: Add to cart → Select payment method → Place order → View instructions → Upload proof image.
  - Edge cases: Duplicate uploads.
  - Constraints: SHA-256 hashing of uploaded proofs to reject duplicates.
- **Real-time Notifications:** 
  - Description: Users get notified about order status changes.
  - User flow: Admin updates order status → User receives instant notification in UI.
  - Edge cases: User offline (notifications are persisted in DB and fetched on load, cached in `sessionStorage`).

# 4. Authentication & Authorization
- **Auth methods:** Laravel session-based authentication (Breeze-like).
- **Session handling:** Laravel session auth. Deployment profile can use cookie sessions (`SESSION_DRIVER=cookie`) for simplicity on single-instance demos, or Redis sessions in production.
- **RLS explanation:** Not using Postgres/Supabase RLS. Security is implemented at the application level via Laravel Policies (`$this->authorize('view', $order)`) and Middleware (`is_admin`, `auth`).

# 5. Database Design
- **Tables:** `users`, `products`, `categories`, `brands`, `product_variants`, `inventories`, `inventory_movements`, `discounts`, `carts`, `orders`, `payments`, `payment_methods`, `notifications`, `coupons`, `order_status_histories`, `refund_requests`, `user_spending`.
- **Relationships:** Highly normalized. Orders have many Items; Products have many Variants; Variants have one Inventory.
- **Constraints:** Foreign key constraints, unique indexes on variants, performance indexes for querying.
- **Triggers:** Handled via Laravel Observers/Services rather than DB-level triggers.
- **Important functions:** DB transactions (`DB::transaction`) are used heavily in `OrderService` and `InventoryService`.

# 6. Realtime System
- **What updates in realtime:** User notifications (e.g., order status updates).
- **How it's implemented:** Laravel Events broadcast over Pusher (`user.{id}` private channels), listened to by Pusher-js/Laravel Echo, and state-managed by Alpine.js.

# 7. Security Model
- **RLS policies:** N/A (App-level authorization).
- **Validation layers:** FormRequests (e.g., `SubmitOrderPaymentRequest`) validate input format and existence.
- **Attack surface analysis:** 
  - **File uploads:** Protected by MIME type checking and duplicate hash blocking.
  - **Race conditions:** Mitigated using DB pessimistic locking during checkout.

# 8. Performance Considerations
- **Query design:** Eager loading (`with('orderItems', 'latestPayment')`) prevents N+1 query problems. Added performance indexes in migrations.
- **Client-side optimization:** Notifications are cached in `sessionStorage` by Alpine.js to reduce unnecessary API calls. Asset bundling via Vite.

# 9. Known Limitations
- Storefront relies on Blade; React SPA mentioned in previous design plans has not been fully integrated.
- Only manual pay-by-transfer is implemented; no automated payment gateways (Stripe/PayPal) are present.

# 10. Future Improvements (REALISTIC ONLY)
- Full integration of React/Vite SPA for the storefront.
- Implementation of automated payment gateways.
- Integration of Cloudflare Turnstile/Honeypot for spam protection on checkout.
