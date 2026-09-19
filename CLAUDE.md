# EcomClaude — Bangladesh B2C Ecommerce Platform

Laravel 13 single-store ecommerce architected for multi-tenant SaaS. Targets the Bangladesh market with COD-first checkout, phone/OTP auth, BDT currency, and BD address structure. PHP 8.4, Filament 3.3, Livewire 3.

See `docs/BRS.md` / `docs/SRS.md` / `docs/FRS.md` for the business/software/functional requirements this build satisfies (each FRS item tagged `[Built]` or `[Planned]`) — re-verify those against code before trusting them, they drift the same way this file does.

## Essential Commands

```bash
# Start development
php artisan serve           # http://localhost:8000
npm run dev                 # Tailwind/Vite hot reload (run in parallel)
# or: composer run dev      # runs serve + queue:listen + pail + vite concurrently

# Database
php artisan migrate:fresh --seed    # Full reset with geo data + store seed
php artisan db:seed                 # Seed only (no migration reset)

# Build assets
npm run build               # Production build

# Cache management
php artisan optimize:clear  # Clear all caches (config, view, route)
php artisan config:clear
php artisan view:clear

# Logs
tail -f storage/logs/laravel.log    # Watch errors (OTP codes logged here in dev)
```

## Docker

`docker/Dockerfile` is a multi-stage build (`composer` → `frontend` (node/vite) → `app` (`php:8.4-fpm`) → `nginx` (`nginx:alpine`, serves built assets + proxies PHP to `app:9000`)) — mirrors the pattern used in the `gas-distribution` (gasflow) repo. Code is **baked into the image at build time, not bind-mounted** — there's no live-reload via Docker; local day-to-day dev should still use `php artisan serve` + `npm run dev` per Essential Commands above. Use the Docker stack for container-parity testing or as the production deploy target, not as the primary dev loop.

`docker/docker-compose.yml` defines `app`, `queue` (same image, overrides command to `queue:work`), and `nginx` — **there is no bundled `mysql` service**. All three point `DB_HOST=host.docker.internal:3306` at a MySQL server running on the **host**, not a container; make sure that host MySQL is up and matches `.env.docker` credentials before starting the stack. `docker/docker-compose.prod.yml` is a production-only overlay (real domain, TLS entrypoint, Traefik's `le` certresolver) — see `DEPLOYMENT.md`.

Nginx publishes a direct fallback port (`APP_PORT`, default `8897` — `8898`/`8899` are already taken by other apps on this host, gov-service/gasflow) independent of Traefik, plus routes via an **external Traefik reverse proxy** (network `proxy`, `external: true`, must already exist/be running separately — shared with other apps on the same host, e.g. gasflow's) matching `Host(\`ecomclaude.local\`) || Host(\`admin.ecomclaude.local\`)` — one nginx service handles both hostnames; Laravel splits them internally (see below). To reach the app in a browser via Traefik you need: (1) Traefik running and attached to the `proxy` network, (2) hosts-file entries mapping both `ecomclaude.local` and `admin.ecomclaude.local` to `127.0.0.1`, (3) `http://` explicitly — the dev `web` entrypoint here is port 80 only, no 443.

### Admin panel: path vs. subdomain
`AdminPanelProvider.php` binds the Filament panel to `config('app.admin_domain')` (from `ADMIN_DOMAIN`) when set, otherwise falls back to `->path('admin')`. `ADMIN_DOMAIN` is **unset** in `.env`/`.env.example` (so `php artisan serve` keeps working at `/admin` with zero setup) but **set** to `admin.ecomclaude.local` in `.env.docker.example` — matching the gasflow (gas-distribution) app's admin-subdomain pattern. In production this becomes a real subdomain (`admin.ecom.yourdomain.tld`, see `DEPLOYMENT.md`), with its own DNS record and its own leg of the Traefik `Host()` rule.

Config lives in `.env.docker` (copy from `.env.docker.example`), not `.env` — Compose only reads a top-level env file for its own `${VAR}` substitution (e.g. `APP_PORT`), so always pass `--env-file`:

```bash
docker compose -f docker/docker-compose.yml --env-file .env.docker up -d --build   # Build and start app, queue, nginx (needs Traefik + host MySQL already running)
docker compose -f docker/docker-compose.yml --env-file .env.docker exec app php artisan migrate:fresh --seed
docker compose -f docker/docker-compose.yml --env-file .env.docker exec app php artisan <command>   # Any artisan command
docker compose -f docker/docker-compose.yml --env-file .env.docker logs -f app     # Tail app container logs
docker compose -f docker/docker-compose.yml --env-file .env.docker down            # Stop stack
```

## CI/CD

Two GitHub Actions workflows, matching gasflow's pattern:

- **`.github/workflows/ci.yml`** — Pint + PHPUnit on every push/PR (not present in gasflow, added here since this repo currently has near-zero test coverage). Runs against sqlite in-memory per `phpunit.xml`, no external DB needed.
- **`.github/workflows/deploy.yml`** — on push to `main`: builds the `app`/`nginx` image targets on GitHub's runners, pushes to GHCR (`ghcr.io/jubaerhossainece/claude-ecom-{app,nginx}`), then SSHes into the VPS to `git pull`, sync `APP_KEY` from a GitHub secret into `.env.docker`, `docker compose pull` + `up -d` with the prod overlay. See `DEPLOYMENT.md` for the full VPS setup runbook and required GitHub secrets (`APP_KEY`, `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`, `VPS_DEPLOY_PATH`).

## Credentials

| Access | URL | Login |
|---|---|---|
| Admin panel (Filament) | `/admin` locally (`php artisan serve`); its own subdomain when `ADMIN_DOMAIN` is set (Docker/prod — see Docker section) | `admin@store.test` / `password` |
| Customer storefront | `/login` | Phone + OTP (shown on screen in debug mode) |

## Project Structure

```
app/
├── Filament/
│   ├── Pages/
│   │   ├── StoreSettings.php       # 6-tab store config: General/Currency/Payment/Delivery/Tax/SEO
│   │   ├── StockAlerts.php         # Low-stock / out-of-stock listing page
│   │   └── InventoryReport.php     # On-hand/reserved/valuation report, CSV export
│   ├── Resources/
│   │   ├── AttributeResource.php   # Custom attribute definitions
│   │   ├── CategoryResource.php    # Nested categories + attribute assignment
│   │   ├── BrandResource.php       # Brand CRUD (logo/website/SEO/products view)
│   │   ├── TagResource.php         # Tag CRUD
│   │   ├── ProductResource.php     # Products with variant repeater, import/export, replicate
│   │   ├── WarehouseResource.php   # Warehouses
│   │   ├── InventoryMovementResource.php  # Stock transfers/damage/movement history
│   │   ├── OrderResource.php       # Order management, COD confirm, refunds, tax
│   │   ├── ReturnRequestResource.php      # Customer return/exchange requests
│   │   └── CustomerResource.php    # Customer list/segments + relation managers
│   └── Widgets/
│       ├── SalesOverviewWidget.php         # Sales stat tiles
│       ├── OperationsOverviewWidget.php    # Low-stock/out-of-stock stat tiles
│       ├── SalesChartWidget.php            # Sales over time
│       ├── OrderStatusChartWidget.php      # Order status distribution
│       ├── PaymentMethodChartWidget.php    # Payment method distribution
│       ├── RevenueByCategoryChartWidget.php
│       ├── CustomerAcquisitionChartWidget.php
│       ├── RecentOrdersWidget.php
│       ├── RecentCustomersWidget.php
│       └── TopSellingProductsWidget.php
├── Http/Controllers/
│   ├── Auth/CustomerAuthController.php  # Phone/OTP flow (rate-limited)
│   ├── CartController.php
│   ├── CheckoutController.php
│   ├── HomeController.php
│   ├── ProductController.php       # Category browse, product detail, search, autocomplete
│   ├── OrderController.php         # Customer-facing order history, invoice, reorder
│   ├── WishlistController.php      # Includes move-to-cart
│   ├── ReviewController.php        # Review CRUD, gated to delivered-order customers
│   ├── ReturnRequestController.php # Customer-initiated return/exchange requests
│   ├── CouponController.php        # Customer-visible coupon list
│   ├── NotificationController.php  # In-app notification list
│   └── ProfileController.php       # Customer profile edit
├── Livewire/
│   ├── AddToCartButton.php         # Quantity picker + add to cart
│   ├── CartBadge.php               # Header cart item count
│   ├── CheckoutForm.php            # Full checkout (cascading geo, delivery calc, coupon, tax)
│   ├── ProductVariantSelector.php  # Dynamic variant picker
│   ├── AddressManager.php          # Customer saved-address CRUD
│   └── NotificationBadge.php       # Header unread-notification count
├── Models/                         # See Data Model section below
├── Services/
│   ├── CartService.php             # Cart CRUD, session↔DB merge on login
│   └── OrderService.php            # Order creation + reserve/commit/release inventory (transactional) — route all order-status transitions through here, not raw model updates
└── Providers/
    └── AppServiceProvider.php      # Binds CartService/OrderService singletons; shares $store with all storefront views

database/
├── migrations/                     # See Migrations section below
└── seeders/
    ├── BangladeshGeoSeeder.php     # 8 divisions, ~25 districts, 70+ thanas
    ├── RolesAndAdminSeeder.php     # admin/store_manager roles + admin@store.test user
    └── StoreSeeder.php             # Default store + all settings + 2 delivery zones

docs/
├── BRS.md                          # Business Requirements Specification
├── SRS.md                          # Software Requirements Specification
└── FRS.md                          # Functional Requirements Specification (itemized, [Built]/[Planned])

resources/views/
├── filament/pages/
├── livewire/
└── storefront/
    ├── layouts/app.blade.php       # Main layout: header, category nav, footer
    ├── home.blade.php
    ├── cart.blade.php
    ├── checkout.blade.php
    ├── checkout-success.blade.php
    ├── auth/{login,register}.blade.php
    ├── account/{orders,order-detail,wishlist,returns,coupons,notifications,profile}.blade.php
    ├── products/{show,category,search}.blade.php
    └── partials/product-card.blade.php
```

## Data Model

### Core tables and their purpose

| Table | Purpose |
|---|---|
| `stores` | One row per store (multi-tenant ready) |
| `store_settings` | Key-value config: payment toggles, delivery charges, tax, theme color, currency, SEO |
| `divisions` / `districts` / `thanas` | Bangladesh geo hierarchy (seeded) |
| `categories` | Nested (parent_id), scoped to store; SEO fields + banner |
| `attributes` | Configurable fields (text/number/select/color/boolean), can be filterable or variant-generating |
| `category_attributes` | Pivot: which attributes belong to which category |
| `brands` | Store-scoped; logo/website/SEO |
| `tags` | Store-scoped; many-to-many with products |
| `products` | Store-scoped; `unit_of_sale` is flexible (piece/kg/gram/litre/pack/etc.) |
| `product_attribute_values` | Flat attribute values per product (non-variant specs) |
| `product_variants` | JSON `attribute_values` column — any combination of attributes; own image/weight/barcode |
| `warehouses` | Store-scoped stock locations |
| `warehouse_stocks` | Per-product/variant, per-warehouse `quantity` + `reserved_quantity` — see reserve/commit/release below |
| `inventory_movements` | Movement log: `transfer_in`/`transfer_out`/damage/adjustment, linked pairs for transfers |
| `customers` | Phone-first (email optional), separate auth guard from `users` |
| `otp_verifications` | Rate-limited OTP records (max 5 attempts, 5-min expiry) |
| `customer_addresses` | BD address: division+district+thana+area+address_line |
| `carts` / `cart_items` | Session cart for guests, DB cart for logged-in customers; merged on login |
| `orders` / `order_items` | Denormalised address/name snapshot for historical accuracy; tax_amount, refunded_amount |
| `order_status_histories` | Full audit trail of status changes |
| `return_requests` | Customer-initiated, per order item; decoupled from `orders.refunded_amount` (admin refunds separately) |
| `delivery_zones` | Per-store, type=district/nationwide, configurable charge + free-delivery threshold |
| `coupons` | Percentage or fixed, global usage limit **and enforced per-customer limit** |
| `wishlists` | Customer-product favourites; tracks price-at-add for price-drop detection |
| `reviews` | Rating+text+photos; gated to customers with a delivered order for that product; approved/moderated |
| `search_queries` | Logged search terms for autocomplete/popular-search surfacing |
| `page_visits` | Logged via `track.visit` middleware; feeds the (still partial) conversion-rate metric |
| `notifications` | Laravel's database notification channel (in-app only — no email/SMS delivery yet) |
| roles/permissions tables | Spatie `laravel-permission` — admin/store_manager roles |
| `media` | Spatie `laravel-medialibrary` — product/variant/category/brand images |
| `imports` / `exports` / `failed_import_rows` | Filament's Excel import/export (product bulk import/export) |

## Key Design Decisions

### Store-scoped data
Every table that belongs to a store has `store_id`. `Store::current()` in `app/Models/Store.php` is the single resolution point — swap this to tenant-aware in Phase 3.

### Attribute flexibility
Attributes are defined at store level, assigned to categories via `category_attributes` pivot. Products store values in `product_attribute_values`. Variants store their combination in `product_variants.attribute_values` (JSON). No code changes needed for new product types.

### Cart merge
`CartService::getOrCreateCart()` checks if a logged-in customer has a session cart and merges it. Called on every cart operation so it's always transparent.

### Inventory: reserve → commit → release
`warehouse_stocks.reserved_quantity` is a separate counter from `quantity` (on-hand). Placing an order **reserves** stock without touching on-hand; shipping **commits** the reservation (decrements on-hand, clears reserved); cancelling before shipment **releases** the reservation; a post-ship return **restores** on-hand. All of this lives in `OrderService` — never mutate `warehouse_stocks` directly from a controller/action, or the reserve/commit/release invariant breaks. This exists specifically because unconfirmed COD orders were previously decrementing on-hand stock at placement time, causing false stockouts.

### COD confirmation flow
`store_settings.cod_confirmation_required = 1` shows a warning to the customer. Admin sees a "Confirm COD" button per order in Filament. Sets `orders.cod_confirmed_at` and bumps status to `confirmed`.

### Returns are decoupled from refunds
A customer's return/exchange request (`ReturnRequestResource`) approval does **not** automatically refund — an admin issues the refund separately via `OrderResource`'s refund action. Don't wire these together without an explicit product decision.

### Auth guards
- `web` guard → `users` table → Filament admin
- `customer` guard → `customers` table → Storefront
- These are fully isolated; never let a check for one silently authorize the other.

### Scout search
`SCOUT_DRIVER=collection` in dev (no external service needed, and not set in `.env`/`.env.example` — this is the package default). Set `SCOUT_DRIVER=meilisearch` + `MEILISEARCH_HOST` in production. Run `php artisan scout:import "App\Models\Product"` after switching.

## Phase Roadmap

### Phase 1 — MVP ✅ COMPLETE
- [x] Flexible product/category/attribute/variant data model
- [x] Store settings layer (DB-driven, not hardcoded)
- [x] Bangladesh geo seeded (Division → District → Thana)
- [x] Cart (session + DB, merged on login)
- [x] COD checkout with delivery zone calculation
- [x] Phone/OTP customer login (email optional)
- [x] Filament admin: store config, categories, attributes, products, orders
- [x] Customer account: order history, reorder, wishlist
- [x] Product search (Laravel Scout, collection driver)
- [x] Low-stock alerts on admin dashboard

### Phase 1.5 — Admin/Customer Panel Expansion ✅ COMPLETE
Built across three review-then-build batches (2026-08-11 → 2026-08-12) against a 25-item gap-analysis plan; see `docs/FRS.md` for the itemized list. Highlights:
- [x] Brand + Tag entities, product bulk import/export, product replicate action
- [x] Category SEO + banner
- [x] Full dashboard (chart widgets, stat tiles, recent-orders/customers lists, top-selling products) + page-visit tracking
- [x] Multi-warehouse inventory with reserve/commit/release stock model, transfers, damage tracking, Stock Alerts + Inventory Report pages
- [x] Order tax (opt-in), refund/partial-refund, printable invoice, richer order filters
- [x] Full `CustomerResource` (admin had none before) with segmentation
- [x] Customer-side: order tracking stepper, wishlist move-to-cart + price-drop tracking, coupon per-customer limit enforcement (was unenforced), reviews (rate/write/photos/edit/delete, delivery-gated), in-app notifications, return/exchange request flow + admin `ReturnRequestResource`, search autocomplete + filters + logging
- [x] Security fixes found during review: customer password/OTP hash cast, login/OTP rate limiting

**Explicitly deferred** (stated, not silently dropped — no structural blocker, just not prioritized): loyalty/rewards program (no real merchant ask yet), live chat (needs a third-party vendor decision), true ML-style personalization (heuristic version built instead), back-in-stock alerts, admin audit log, admin 2FA, customer login history, FAQ/support tickets, customer profile picture upload, recently-viewed/frequently-bought-together.

### Phase 2 — Payments & Couriers (PARTIAL — config surface exists, no live integration)
- [ ] SSLCommerz integration (card, net banking, Dutch-Bangla) — `payment_sslcommerz_enabled` toggle and `Order::PAYMENT_METHODS['sslcommerz']` exist; no gateway calls happen
- [ ] bKash Mobile Banking API — same: toggle + constant exist, not integrated
- [ ] Pathao courier API (label generation, tracking webhook) — `courier_name`/`courier_tracking_id` fields exist on `orders`, no API calls
- [ ] Steadfast courier API
- [ ] RedX courier API
- [ ] Payment method pluggable pattern: `app/Services/Payment/{SSLCommerz,BKash}Service.php` (directory doesn't exist yet)
- [ ] Courier pluggable pattern: `app/Services/Courier/{Pathao,Steadfast,RedX}Service.php` (directory doesn't exist yet)
- [ ] Real SMS gateway for OTP/notifications (currently `Log::info` / in-app database channel only)

### Phase 3 — Multi-Tenancy SaaS (NOT STARTED)
- [ ] `stancl/tenancy` package integration
- [ ] Tenant-aware `Store::current()` resolution from domain/subdomain
- [ ] Super-admin panel (manage all tenants)
- [ ] Subscription billing (per-store plan)
- [ ] Custom domain per tenant
- [ ] Per-store theming (CSS variables already in layout, driven by `store_settings.primary_color`)

## Adding New Features — Guidelines

**New product type:** Just add attributes in Filament → Attributes, assign to a category. No code changes.

**New payment method:**
1. Add toggle to `store_settings` (e.g. `payment_nagad_enabled`)
2. Add to `Order::PAYMENT_METHODS` constant
3. Create `app/Services/Payment/NagadService.php`
4. Add option in `CheckoutForm.php` livewire component
5. Add in `StoreSettings.php` Filament page

**New courier:**
1. Create `app/Services/Courier/NewCourierService.php` implementing a `CourierInterface`
2. Add courier name option in order edit form

**New store setting:**
1. Add to `StoreSeeder.php` defaults
2. Add field in `app/Filament/Pages/StoreSettings.php`
3. Read via `$store->getSetting('key', $default)` anywhere

**Migrations:** this project consolidates migrations one-file-per-table rather than create+add-column pairs. Add new columns directly to the original create-table migration and hand-apply a matching `ALTER TABLE` on the live dev DB — don't create a new `add_column_to_x_table` migration file.

## Environment Variables

```env
# Core
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost          # http://ecomclaude.local when running the docker/ stack behind Traefik

# DB (SQLite for dev by default per .env.example, MySQL also supported)
DB_CONNECTION=sqlite
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=ecom_claude
# DB_USERNAME=ecom_user
# DB_PASSWORD=secret

# Search
SCOUT_DRIVER=collection          # dev default (package default, not explicitly set)
# SCOUT_DRIVER=meilisearch       # prod
# MEILISEARCH_HOST=http://127.0.0.1:7700

# Phase 2 — Payment (not yet consumed by any code — toggles in store_settings exist, gateway calls don't)
# SSLCOMMERZ_STORE_ID=
# SSLCOMMERZ_STORE_PASSWORD=
# BKASH_APP_KEY=
# BKASH_APP_SECRET=

# SMS gateway (Phase 2 — replace Log::info OTP with real SMS)
# SMS_GATEWAY_KEY=
# SMS_GATEWAY_URL=
```

## Production Checklist

- [ ] Switch `DB_CONNECTION` to MySQL and run migrations
- [ ] Set `APP_DEBUG=false` and `APP_ENV=production`
- [ ] Set `SCOUT_DRIVER=meilisearch` and start Meilisearch service
- [ ] Run `php artisan scout:import "App\Models\Product"`
- [ ] Set `SESSION_DRIVER=database` (already in `.env.example`)
- [ ] Configure real SMS gateway for OTP (replace `Log::info` in `CustomerAuthController`)
- [x] Set up queue worker (`QUEUE_CONNECTION=database`) — handled by the `queue` service in `docker/docker-compose.yml` when deploying via Docker; a bare-metal deploy still needs its own supervisor/cron equivalent
- [ ] Configure `FILESYSTEM_DISK=s3` for media uploads (optional)
- [ ] Run `php artisan filament:upgrade` after any Filament package update
- [ ] Set up cron: `* * * * * php artisan schedule:run`
