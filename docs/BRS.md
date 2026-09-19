# Business Requirements Specification (BRS)
## EcomClaude — Bangladesh B2C Ecommerce Platform

**Document version:** 1.0
**Date:** 2026-08-13
**Status:** Reflects the system as currently built (Phase 1 complete + Phase 1.5 admin/customer panel expansion). Phase 2 (payments/couriers) and Phase 3 (multi-tenancy) are business intent, not yet implemented — flagged accordingly throughout.

---

## 1. Purpose

Define the business rationale, goals, and scope for EcomClaude: a single-store B2C ecommerce web application purpose-built for the Bangladesh market, architected so it can later become a multi-tenant SaaS product (Phase 3).

## 2. Business Background

Generic ecommerce platforms (Shopify, WooCommerce, etc.) are not well-suited to the Bangladesh market's specific commerce norms:

- **Cash on Delivery (COD) is the dominant payment method**, not an edge case — most platforms treat it as a secondary/optional method.
- **Address structure is hierarchical and non-Western**: Division → District → Thana, not "state/city/zip."
- **Phone number is the primary identity**, not email — customers expect to log in and be contacted by phone/OTP, with email optional.
- **Delivery pricing is zone-based** (e.g., "inside Dhaka" vs "outside Dhaka"), not carrier-calculated shipping.
- Local courier and payment gateway integrations (Pathao, Steadfast, RedX, bKash, SSLCommerz) are business-critical, not nice-to-haves.

EcomClaude's business case is to bake these norms into the platform's core data model and checkout flow rather than bolting them on as customizations, so store owners get a Bangladesh-correct experience out of the box.

## 3. Business Objectives

| # | Objective | How the built system addresses it |
|---|---|---|
| 1 | Let a merchant sell any kind of physical product without developer involvement per product type | Flexible attribute/variant system — new product types are configured in the admin, not coded |
| 2 | Support COD-first commerce with low customer friction | Phone/OTP login (no password to remember), COD as the default and only currently-functional payment method |
| 3 | Give store owners full visibility into sales, inventory, and customers | Admin dashboard (stat widgets + charts), inventory reports, stock alerts, customer segmentation |
| 4 | Reduce COD-related loss (non-delivery, false stockouts) | COD phone-confirmation workflow; reserve→commit→release inventory model tied to order lifecycle, not order placement |
| 5 | Build a foundation that can become multi-tenant SaaS later | `store_id` scoping on every business table from day one; `Store::current()` as the single tenant-resolution point |
| 6 | Keep the platform config-driven, not hardcoded | Store settings (currency, delivery charges, tax, payment toggles, SEO) editable by the store owner without a deploy |

## 4. Scope

### 4.1 In scope (built and operating today)

- Storefront: browsing, search, cart, checkout, phone/OTP account, order history, wishlist, reviews, returns, coupons, notifications.
- Admin panel (Filament): full catalog management (products, variants, categories, attributes, brands, tags), inventory across warehouses, order lifecycle management, customer management, coupons, returns, dashboard analytics, store configuration.
- Bangladesh-specific data: seeded Division/District/Thana geography; BDT currency; zone-based delivery pricing.
- Single store, single currency, single language pair (English/Bengali labels) per deployment.

### 4.2 Explicitly out of scope for the current build (Phase 2/3, business intent only)

- **Live payment gateway processing** (SSLCommerz, bKash) — the *choice* of these methods exists in configuration, but no gateway integration is implemented; COD is the only payment method that actually functions end-to-end today.
- **Courier API integration** (Pathao, Steadfast, RedX) — tracking fields exist on orders, but no courier API calls, label generation, or webhook handling exist.
- **Multi-tenancy / SaaS** — the data model is tenant-ready (`store_id` everywhere) but there is one hard-coded store; no tenant provisioning, subdomain routing, or billing exists.
- **Real SMS delivery** — OTPs and notifications are logged to the application log in development; no SMS gateway is wired up.
- Loyalty/rewards program — deferred pending a real merchant request (no business case yet).
- Live chat support — deferred pending a third-party vendor decision.

## 5. Stakeholders

| Stakeholder | Interest |
|---|---|
| Store owner / admin | Runs the shop day-to-day: manages catalog, fulfills orders, confirms COD, tracks inventory and sales |
| Store manager (secondary admin role) | Subset of admin capabilities |
| Customer | Browses, buys, tracks orders, manages wishlist/reviews/returns |
| Platform operator (future, Phase 3) | Runs EcomClaude as a SaaS product across many stores |

## 6. Key Business Rules

- A product's sellable unit is flexible (piece, kg, gram, litre, pack, etc.) — not assumed to be "each."
- Every order snapshots the customer's name/address/phone at time of purchase, independent of later changes to the customer's saved profile — required for accurate historical records and dispute resolution.
- Inventory is **reserved** at order placement and only **committed** (permanently deducted) at shipment; cancelling a pending order releases the reservation rather than requiring a manual restock. This directly protects against false stockouts on unconfirmed COD orders, a real problem identified during the Phase 1.5 review.
- Coupons enforce a configurable per-customer usage limit, not just a global usage cap.
- A customer can only review a product they have an order for that reached "delivered" status — reviews cannot be posted speculatively.
- Return/exchange requests are a customer-initiated workflow separate from admin-issued refunds; approving a return request does not automatically trigger a refund — a store admin makes that call explicitly.

## 7. Success Criteria

- A store owner can list a new product category with entirely different attributes (e.g., adding "grocery" alongside "electronics") with zero code changes.
- COD order confirmation and inventory numbers stay accurate under concurrent unconfirmed orders (no phantom stockouts).
- Admin can answer "what sold, to whom, and what's low on stock" from the dashboard without exporting data elsewhere.
- The system can be pointed at a second store's data by changing `Store::current()`'s resolution logic alone (validates the Phase 3 SaaS path), without schema changes.

## 8. Assumptions & Constraints

- Single deployment = single store today (Phase 1/1.5); Phase 3 multi-tenancy is a planned re-architecture of `Store::current()`, not yet built.
- Bangladesh geography (divisions/districts/thanas) is seeded data, not user-editable in the admin.
- The platform assumes PHP 8.4 / Laravel hosting; no serverless or non-PHP deployment target is in scope.
- Search runs on Laravel Scout's `collection` driver in development; production requires standing up Meilisearch — a deployment dependency, not a code gap.
