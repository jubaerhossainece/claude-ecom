# Software Requirements Specification (SRS)
## EcomClaude — Bangladesh B2C Ecommerce Platform

**Document version:** 1.0
**Date:** 2026-08-13
**Companion documents:** [BRS.md](BRS.md) (business rationale), [FRS.md](FRS.md) (itemized functional requirements)

---

## 1. Introduction

### 1.1 Purpose
This SRS specifies the software-level requirements for EcomClaude, translating the business goals in the BRS into system behavior, interfaces, and quality attributes. It is written against the system as it exists in the repository today, not an aspirational future state; planned-but-unbuilt work is explicitly marked **[Phase 2]** or **[Phase 3]**.

### 1.2 Scope
EcomClaude is a server-rendered web application (Laravel 13 + Livewire 3 for interactivity, Filament 3 for the admin panel) providing:
- A public storefront for browsing and purchasing products.
- A phone/OTP-authenticated customer account area.
- An admin panel for catalog, inventory, order, and customer management.

### 1.3 Definitions
| Term | Meaning |
|---|---|
| Store | A single tenant/shop record; all business data is scoped to one via `store_id` |
| COD | Cash on Delivery |
| OTP | One-Time Password, sent (currently logged, not SMS'd) for customer login |
| Variant | A specific sellable combination of a product's attributes (e.g., size+color), tracked with its own stock |
| Thana | Sub-district administrative unit in Bangladesh (below District, below Division) |
| Guard | Laravel's term for an independent authentication context — this system has two: `web` (admin users) and `customer` (storefront customers) |

### 1.4 References
- `CLAUDE.md` (repository root) — architecture and phase roadmap.
- `database/migrations/` — authoritative schema.
- `app/Filament/Resources/`, `app/Http/Controllers/`, `app/Livewire/` — authoritative feature inventory.

---

## 2. Overall Description

### 2.1 Product Perspective
EcomClaude is a standalone monolithic Laravel application. It is not a component of a larger system, but its data model is deliberately tenant-ready so that a future SaaS wrapper (Phase 3, `stancl/tenancy`) can be layered on without a schema rewrite.

### 2.2 Product Functions (summary — see FRS for detail)
- Catalog management with configurable attributes and product variants.
- Cart (guest session + authenticated, auto-merged on login) and COD-first checkout with zone-based delivery pricing.
- Phone/OTP customer authentication, separate from admin's email/password authentication.
- Order lifecycle management including COD confirmation, status history, refunds, returns.
- Warehouse-based inventory with reserve/commit/release stock semantics.
- Coupons, wishlists, product reviews, in-app notifications.
- Admin analytics dashboard (stat tiles + charts) and reporting (inventory valuation, stock alerts).
- Store-level configuration (currency, payment method toggles, delivery charges, tax, SEO) editable without a deploy.

### 2.3 User Classes

| Class | Guard | Access |
|---|---|---|
| Admin | `web` | Full Filament panel: catalog, inventory, orders, customers, settings |
| Store Manager | `web` | Same panel, role-restricted subset (via Spatie permissions) |
| Customer | `customer` | Storefront + `/account` self-service area |
| Guest | none | Storefront browsing, cart (session-based), must authenticate to check out account features |

### 2.4 Operating Environment
- **Backend:** PHP 8.4, Laravel 13.8+, MySQL (production) / SQLite (dev), Filament 3.3, Livewire 3.
- **Frontend:** Server-rendered Blade + Livewire, Tailwind CSS, Vite build pipeline.
- **Search:** Laravel Scout — `collection` driver (in-process, no external service) in development; Meilisearch in production.
- **Containerization:** Docker Compose stack (`app` PHP-FPM, `nginx`, `mysql`), optionally fronted by Traefik for custom local domains.

### 2.5 Design & Implementation Constraints
- Every business-data table carries `store_id`; queries must go through store-scoped relationships or `Store::current()` — there is no global, cross-store query path today.
- Two independent auth guards (`web`, `customer`) must never be conflated — admin sessions cannot access `/account`, and customer sessions cannot access `/admin`.
- Product attribute/variant flexibility is achieved through EAV-style tables (`product_attribute_values`, `product_variants.attribute_values` JSON), not per-category schema changes — new product types must never require a migration.
- Inventory correctness depends on the reserve→commit→release state machine on `warehouse_stocks`; any new order-status transition must go through `OrderService`, not raw model updates, to avoid bypassing stock accounting.

### 2.6 Assumptions & Dependencies
- Single store per deployment today; Phase 3 tenancy is a dependency for multi-store operation.
- SMS gateway is not yet integrated — OTP delivery currently depends on the admin/customer reading `storage/logs/laravel.log` (dev) or the on-screen debug display.
- SSLCommerz/bKash and courier APIs are external dependencies not yet integrated (Phase 2); their configuration surface exists (`store_settings` toggles, `Order::PAYMENT_METHODS`) but no live requests occur.

---

## 3. System Features (high-level; see FRS for itemized requirements)

1. Product & Catalog Management
2. Inventory Management (multi-warehouse)
3. Cart & Checkout
4. Customer Authentication (Phone/OTP)
5. Order Management (admin + customer-facing)
6. Returns & Refunds
7. Coupons & Promotions
8. Reviews & Ratings
9. Wishlist
10. Search (autocomplete, filters, logging)
11. Notifications (in-app)
12. Admin Analytics Dashboard
13. Store Configuration

---

## 4. Non-Functional Requirements

### 4.1 Performance
- Product search must return results without a page reload for autocomplete (AJAX endpoint, `/api/search-autocomplete`).
- Dashboard chart widgets must not run unbounded queries — all are scoped to `Store::current()` and typically windowed (e.g., by date range).

### 4.2 Security
- Passwords/OTP verification: `otp_verifications` enforces a maximum of 5 attempts and a 5-minute expiry window.
- Login and OTP-request endpoints are rate-limited (`throttle:6,1` for OTP send/register, `throttle:10,1` for OTP verify) — added specifically after a Phase 1.5 audit found these routes unprotected.
- The `customer` guard's password/OTP hash field must always be cast through Laravel's `hashed` cast — a prior bug where this was missing was fixed during the Phase 1.5 review.
- Admin and customer sessions are fully isolated via separate Laravel auth guards.

### 4.3 Reliability / Data Integrity
- Inventory changes must never be able to double-deduct or under-restore stock across cancel/return/ship transitions — enforced by the reserve/commit/release model in `OrderService`, not scattered controller logic.
- Order records denormalize customer name/address at time of purchase so historical orders remain accurate even if the customer later edits their profile.

### 4.4 Usability
- Storefront supports English and Bengali labels for geography (division/district/thana `bn_name` fields) and store language setting.
- Admin panel provides visual affordances for operational states: order status badges, COD-confirmation call-to-action, low-stock alerts.

### 4.5 Maintainability
- New product types require zero code changes (attribute/category configuration only) — a explicit architectural goal, not just a side effect.
- New payment methods and couriers follow a documented pluggable-service pattern (`app/Services/Payment/*Service.php`, `app/Services/Courier/*Service.php` implementing a common interface) — scaffolded as a pattern in CLAUDE.md even though concrete implementations are Phase 2 work.

### 4.6 Compatibility
- Currency and number formatting must remain correct for BDT (৳) across admin and storefront; known limitation — PHP's `intl` currency formatter falls back to the `BDT` ISO prefix rather than the Taka glyph under the `en` locale in some contexts (observed in admin order views).

---

## 5. External Interface Requirements

### 5.1 User Interfaces
- **Storefront:** Blade views under `resources/views/storefront/`, Tailwind-styled, Livewire-driven cart/checkout/variant-selection components.
- **Admin panel:** Filament 3 panel at `/admin`, resource-based CRUD + custom pages (Store Settings, Stock Alerts, Inventory Report) + dashboard widgets.

### 5.2 Hardware Interfaces
None beyond standard web server hosting; no device-specific integration.

### 5.3 Software Interfaces
- **Database:** MySQL 8 (prod) / SQLite (dev) via Eloquent ORM.
- **Search:** Meilisearch (prod) via Laravel Scout HTTP API — **[Phase config dependency, not yet stood up by default]**.
- **Media storage:** Spatie Media Library, local disk by default, S3-capable — **[optional, not required by current deployment]**.
- **Payment gateways (SSLCommerz, bKash):** **[Phase 2 — not integrated]**.
- **Courier APIs (Pathao, Steadfast, RedX):** **[Phase 2 — not integrated]**.
- **SMS gateway:** **[Phase 2 — not integrated; `Log::info` stands in for OTP delivery today]**.

### 5.4 Communication Interfaces
Standard HTTPS; no other network protocol requirements.

---

## 6. Other Requirements

- **Localization data:** Bangladesh geography (8 divisions, ~25 districts, 70+ thanas) must be seed-loadable via `BangladeshGeoSeeder` and is a hard prerequisite for checkout address selection to function.
- **Auditability:** Every order status change must be recorded in `order_status_histories`, not just reflected in the current `orders.status` value.
