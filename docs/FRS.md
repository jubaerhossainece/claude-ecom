# Functional Requirements Specification (FRS)
## EcomClaude — Bangladesh B2C Ecommerce Platform

**Document version:** 1.0
**Date:** 2026-08-13
**Companion documents:** [BRS.md](BRS.md) (business rationale), [SRS.md](SRS.md) (system-level requirements)

Each requirement is tagged **[Built]** (implemented and operating in the current codebase) or **[Planned]** (business intent, not yet implemented — Phase 2/3 per the roadmap). IDs are stable identifiers for cross-referencing, not sequence-sensitive.

---

## 1. Catalog Management (Admin)

| ID | Requirement | Status |
|---|---|---|
| FR-CAT-01 | Admin can create/edit/delete nested categories (parent/child), scoped to the store | Built |
| FR-CAT-02 | Admin can set category SEO fields (`meta_title`, `meta_description`) and a banner image, separate from the category thumbnail | Built |
| FR-CAT-03 | Admin can define custom attributes (text/number/select/color/boolean) at store level and assign them to categories via a pivot | Built |
| FR-CAT-04 | Admin can mark an attribute as "variant-generating" so it drives product variant combinations, or "filterable" so it appears as a storefront filter | Built |
| FR-CAT-05 | Admin can create/edit/delete Brand records with logo, description, website, and SEO fields, and view a brand's product list | Built |
| FR-CAT-06 | Admin can create/edit/delete Tag records and assign multiple tags to a product | Built |
| FR-CAT-07 | Admin can create a product with: name, slug, description, unit of sale (piece/kg/gram/litre/pack/etc.), category, brand, tags, flat attribute values, and a variant repeater (any attribute combination, each with its own price/stock/image/weight/barcode) | Built |
| FR-CAT-08 | Admin can duplicate ("Replicate") an existing product, including its variants | Built |
| FR-CAT-09 | Admin can bulk import/export products via CSV/Excel with column mapping and validation | Built |
| FR-CAT-10 | Admin can set a product's lifecycle status (draft/active/inactive/archived) | Built |
| FR-CAT-11 | Storefront product search is indexed via Laravel Scout (collection driver in dev, Meilisearch in prod) | Built |

## 2. Inventory Management (Admin)

| ID | Requirement | Status |
|---|---|---|
| FR-INV-01 | Stock is tracked per product/variant **per warehouse**, not as a single global number | Built |
| FR-INV-02 | Placing an order **reserves** stock (`warehouse_stocks.reserved_quantity`) without deducting on-hand quantity | Built |
| FR-INV-03 | Shipping an order **commits** the reservation (deducts on-hand, clears reserved) | Built |
| FR-INV-04 | Cancelling a pending/unshipped order **releases** the reservation without any manual restock step | Built |
| FR-INV-05 | A post-shipment return **restores** on-hand stock | Built |
| FR-INV-06 | Admin can transfer stock between warehouses, recorded as linked `transfer_out`/`transfer_in` inventory movements | Built |
| FR-INV-07 | Admin can record damaged stock, separate from ordinary stock movements | Built |
| FR-INV-08 | A Stock Alerts admin page lists low-stock and out-of-stock products/variants | Built |
| FR-INV-09 | An Inventory Report admin page shows on-hand/reserved quantity and valuation, exportable as CSV | Built |

## 3. Storefront — Browsing & Search

| ID | Requirement | Status |
|---|---|---|
| FR-BRW-01 | Home page displays store content driven by `store_settings`, not hardcoded | Built |
| FR-BRW-02 | Customers can browse products by category (including nested categories) | Built |
| FR-BRW-03 | Customers can view a product detail page with variant selection (Livewire `ProductVariantSelector`) that updates price/stock/image live | Built |
| FR-BRW-04 | Customers can search products by keyword, with autocomplete (`/api/search-autocomplete`) | Built |
| FR-BRW-05 | Search results support category and price filters | Built |
| FR-BRW-06 | Recent and popular search terms are logged (`search_queries` table) for admin visibility | Built |

## 4. Cart & Checkout

| ID | Requirement | Status |
|---|---|---|
| FR-CRT-01 | Guests can add items to a session-based cart without logging in | Built |
| FR-CRT-02 | Logged-in customers have a DB-backed cart | Built |
| FR-CRT-03 | A guest's session cart is merged into the customer's DB cart automatically on login (`CartService::getOrCreateCart()`) | Built |
| FR-CRT-04 | Checkout collects a cascading Division → District → Thana address (Livewire, AJAX-populated dropdowns via `/api/districts/{division}` and `/api/thanas/{district}`) | Built |
| FR-CRT-05 | Delivery charge is computed from the store's delivery zones (e.g., inside/outside Dhaka), with a configurable free-delivery threshold | Built |
| FR-CRT-06 | Customers can apply a coupon code at checkout | Built |
| FR-CRT-07 | Tax is applied to the order total when `tax_enabled` is on, at the configured `tax_rate_percent` | Built |
| FR-CRT-08 | If `cod_confirmation_required` is enabled, the customer sees a COD confirmation notice at checkout | Built |
| FR-CRT-09 | Order creation is transactional and deducts (reserves) inventory atomically with order/order-item creation (`OrderService`) | Built |
| FR-CRT-10 | Customer sees an order confirmation page after successful checkout | Built |
| FR-CRT-11 | Live payment (SSLCommerz card/net-banking, bKash) at checkout | Planned (Phase 2) |

## 5. Customer Authentication & Account

| ID | Requirement | Status |
|---|---|---|
| FR-AUTH-01 | Customers authenticate by phone number + OTP, on a separate `customer` guard from admin `web` auth | Built |
| FR-AUTH-02 | OTP requests are rate-limited (6/min) and OTP verification is rate-limited (10/min) | Built |
| FR-AUTH-03 | OTP records expire after 5 minutes and lock out after 5 failed attempts | Built |
| FR-AUTH-04 | Email is optional on customer registration; phone is required | Built |
| FR-AUTH-05 | Customer OTP/password hashes use Laravel's `hashed` cast (fixed prior gap where this wasn't applied) | Built |
| FR-AUTH-06 | Customer can manage saved addresses (`AddressManager` Livewire component) | Built |
| FR-AUTH-07 | Customer can edit profile (name, email, phone) via `/account/profile` | Built |
| FR-AUTH-08 | Real SMS delivery of OTP codes | Planned (Phase 2 — currently logged, not sent) |

## 6. Order Management

| ID | Requirement | Status |
|---|---|---|
| FR-ORD-01 | Admin can view/filter orders by status, payment status, date range, amount range, customer, and product | Built |
| FR-ORD-02 | Admin can change order status; every change is recorded in `order_status_histories` for audit | Built |
| FR-ORD-03 | Admin has a one-click "Confirm COD" action that sets `cod_confirmed_at`/`cod_confirmed_by` and advances status | Built |
| FR-ORD-04 | Admin can issue a full or partial refund against an order, updating `refunded_amount` and `payment_status` | Built |
| FR-ORD-05 | A printable invoice view exists for both admin and customer (`/account/orders/{order}/invoice`, `/admin/orders/{order}/invoice`) | Built |
| FR-ORD-06 | Customer can view order history and individual order detail/status | Built |
| FR-ORD-07 | Customer can reorder a past order (re-adds items to cart) | Built |
| FR-ORD-08 | Customer sees a visual order-tracking stepper reflecting current status | Built |
| FR-ORD-09 | Courier label generation / live tracking webhook (Pathao/Steadfast/RedX) | Planned (Phase 2 — tracking fields exist, no API integration) |

## 7. Returns & Refunds

| ID | Requirement | Status |
|---|---|---|
| FR-RET-01 | Customer can submit a return/exchange request against a specific order item | Built |
| FR-RET-02 | Admin manages return requests via a dedicated `ReturnRequestResource`, independent of the refund action on `OrderResource` | Built |
| FR-RET-03 | Approving a return request does **not** automatically issue a refund — an admin must separately trigger the refund | Built |
| FR-RET-04 | Customer can view the status of their return requests (`/account/returns`) | Built |

## 8. Coupons & Promotions

| ID | Requirement | Status |
|---|---|---|
| FR-CPN-01 | Admin can create percentage or fixed-value coupons with usage limits and expiry | Built |
| FR-CPN-02 | Coupons enforce a **per-customer** usage limit, not just a global cap (fixed — was defined in schema but unenforced) | Built |
| FR-CPN-03 | Customer can view coupons available to them (`/account/coupons`) | Built |

## 9. Reviews & Ratings

| ID | Requirement | Status |
|---|---|---|
| FR-REV-01 | Customer can rate and write a review with photos on a product | Built |
| FR-REV-02 | Review submission is gated to customers who have a **delivered** order containing that product | Built |
| FR-REV-03 | Customer can edit or delete their own review | Built |
| FR-REV-04 | Reviews require admin approval/moderation before appearing publicly | Built |

## 10. Wishlist

| ID | Requirement | Status |
|---|---|---|
| FR-WSH-01 | Customer can add/remove products to a wishlist | Built |
| FR-WSH-02 | Customer can move a wishlist item directly to cart | Built |
| FR-WSH-03 | System tracks the price at time a product was wishlisted, to surface price drops | Built |

## 11. Notifications

| ID | Requirement | Status |
|---|---|---|
| FR-NTF-01 | Customer receives in-app notifications (order status changes, etc.) via Laravel's database notification channel | Built |
| FR-NTF-02 | Customer can view notifications at `/account/notifications`, with an unread-count badge (Livewire `NotificationBadge`) | Built |
| FR-NTF-03 | Real email/SMS notification delivery | Planned (Phase 2) |

## 12. Customer Management (Admin)

| ID | Requirement | Status |
|---|---|---|
| FR-CUS-01 | Admin can view/search all customers with order/spend history | Built |
| FR-CUS-02 | Admin can segment customers (new/returning/VIP/inactive) | Built |
| FR-CUS-03 | Admin can view a customer's addresses, orders, and wishlist via relation managers on `CustomerResource` | Built |

## 13. Admin Analytics Dashboard

| ID | Requirement | Status |
|---|---|---|
| FR-DSH-01 | Dashboard shows sales stat tiles: today's sales, monthly/yearly revenue, total/pending/completed/cancelled orders, average order value, total/new customers | Built |
| FR-DSH-02 | Dashboard shows operational stat tiles: low-stock and out-of-stock counts | Built |
| FR-DSH-03 | Dashboard includes chart widgets: sales over time, order status distribution, payment method distribution, revenue by category, customer acquisition | Built |
| FR-DSH-04 | Dashboard lists recent orders and recent customer registrations | Built |
| FR-DSH-05 | Dashboard shows a top-selling-products widget | Built |
| FR-DSH-06 | Page-visit tracking (`track.visit` middleware → `page_visits` table) exists to support conversion-rate calculation | Built |
| FR-DSH-07 | True conversion-rate metric (visits → purchases funnel) | Planned (data collection exists; funnel metric not yet surfaced) |

## 14. Store Configuration (Admin)

| ID | Requirement | Status |
|---|---|---|
| FR-CFG-01 | Admin can edit General settings: name, tagline, description, support phone/email, default language, theme color | Built |
| FR-CFG-02 | Admin can edit Currency settings: currency code and symbol | Built |
| FR-CFG-03 | Admin can toggle payment methods available at checkout (COD, SSLCommerz, bKash) and configure COD confirmation requirement/message | Built (toggles only — SSLCommerz/bKash processing itself is Planned) |
| FR-CFG-04 | Admin can configure Delivery charges (inside/outside Dhaka) and free-delivery threshold | Built |
| FR-CFG-05 | Admin can enable/configure Tax rate | Built |
| FR-CFG-06 | Admin can edit store-level SEO meta title/description | Built |

## 15. Access Control

| ID | Requirement | Status |
|---|---|---|
| FR-ACC-01 | Admin panel (`/admin`) and storefront/account (`/account`) use fully separate Laravel auth guards (`web` vs `customer`) | Built |
| FR-ACC-02 | Admin roles (admin, store_manager) are permission-scoped via Spatie roles/permissions | Built |
| FR-ACC-03 | Admin two-factor authentication | Planned |
| FR-ACC-04 | Admin audit log of admin actions | Planned |
| FR-ACC-05 | Customer login history | Planned |
