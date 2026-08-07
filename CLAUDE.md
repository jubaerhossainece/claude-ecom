# EcomClaude — Bangladesh B2C Ecommerce Platform

Laravel 11 single-store ecommerce architected for multi-tenant SaaS. Targets the Bangladesh market with COD-first checkout, phone/OTP auth, BDT currency, and BD address structure.

## Essential Commands

```bash
# Start development
php artisan serve           # http://localhost:8000
npm run dev                 # Tailwind/Vite hot reload (run in parallel)

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

Containerized stack: `app` (PHP 8.4-FPM), `nginx` (port 8000), `mysql` (port 3307 on host to avoid clashing with any native MySQL). Node/npm run on the host, not in a container — `npm run dev`/`npm run build` still apply as above.

```bash
docker compose up -d --build   # Build and start app, nginx, mysql
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan <command>   # Any artisan command
docker compose logs -f app     # Tail app container logs
docker compose down            # Stop stack (add -v to also drop the mysql volume)
```

## Credentials

| Access | URL | Login |
|---|---|---|
| Admin panel (Filament) | `/admin` | `admin@store.test` / `password` |
| Customer storefront | `/login` | Phone + OTP (shown on screen in debug mode) |

## Project Structure

```
app/
├── Filament/
│   ├── Pages/
│   │   └── StoreSettings.php       # 5-tab store config page
│   ├── Resources/
│   │   ├── AttributeResource.php   # Custom attribute definitions
│   │   ├── CategoryResource.php    # Nested categories + attribute assignment
│   │   ├── OrderResource.php       # Order management with COD confirm action
│   │   └── ProductResource.php     # Products with variant repeater
│   └── Widgets/
│       └── SalesOverviewWidget.php # Dashboard stats
├── Http/Controllers/
│   ├── Auth/CustomerAuthController.php  # Phone/OTP flow
│   ├── CartController.php
│   ├── CheckoutController.php
│   ├── HomeController.php
│   ├── OrderController.php         # Customer-facing order history
│   ├── ProductController.php       # Category browse, product detail, search
│   └── WishlistController.php
├── Livewire/
│   ├── AddToCartButton.php         # Quantity picker + add to cart
│   ├── CartBadge.php               # Header cart item count
│   ├── CheckoutForm.php            # Full checkout (cascading geo, delivery calc, coupon)
│   └── ProductVariantSelector.php  # Dynamic variant picker
├── Models/                         # See Data Model section below
├── Services/
│   ├── CartService.php             # Cart CRUD, session↔DB merge on login
│   └── OrderService.php            # Order creation with inventory deduction (transactional)
└── Providers/
    └── AppServiceProvider.php      # Binds CartService/OrderService singletons; shares $store with all storefront views

database/
├── migrations/                     # See Migrations section below
└── seeders/
    ├── BangladeshGeoSeeder.php     # 8 divisions, ~25 districts, 70+ thanas
    ├── RolesAndAdminSeeder.php     # admin/store_manager roles + admin@store.test user
    └── StoreSeeder.php             # Default store + all settings + 2 delivery zones

resources/views/
├── filament/pages/
│   └── store-settings.blade.php
├── livewire/
│   ├── add-to-cart-button.blade.php
│   ├── cart-badge.blade.php
│   ├── checkout-form.blade.php
│   └── product-variant-selector.blade.php
└── storefront/
    ├── layouts/app.blade.php       # Main layout: header, category nav, footer
    ├── home.blade.php
    ├── cart.blade.php
    ├── checkout.blade.php
    ├── checkout-success.blade.php
    ├── auth/{login,register}.blade.php
    ├── account/{orders,order-detail,wishlist}.blade.php
    ├── products/{show,category,search}.blade.php
    └── partials/product-card.blade.php
```

## Data Model

### Core tables and their purpose

| Table | Purpose |
|---|---|
| `stores` | One row per store (multi-tenant ready) |
| `store_settings` | Key-value config: payment toggles, delivery charges, theme color, currency, SMS messages |
| `divisions` / `districts` / `thanas` | Bangladesh geo hierarchy (seeded) |
| `categories` | Nested (parent_id), scoped to store |
| `attributes` | Configurable fields (text/number/select/color/boolean), can be filterable or variant-generating |
| `category_attributes` | Pivot: which attributes belong to which category |
| `products` | Store-scoped; `unit_of_sale` is flexible (piece/kg/gram/litre/pack/etc.) |
| `product_attribute_values` | Flat attribute values per product (non-variant specs) |
| `product_variants` | JSON `attribute_values` column — any combination of attributes |
| `customers` | Phone-first (email optional), separate auth guard from `users` |
| `otp_verifications` | Rate-limited OTP records (max 5 attempts, 5-min expiry) |
| `customer_addresses` | BD address: division+district+thana+area+address_line |
| `carts` / `cart_items` | Session cart for guests, DB cart for logged-in customers; merged on login |
| `orders` / `order_items` | Denormalised address/name snapshot for historical accuracy |
| `order_status_histories` | Full audit trail of status changes |
| `delivery_zones` | Per-store, type=district/nationwide, configurable charge + free-delivery threshold |
| `coupons` | Percentage or fixed, usage limits, expiry |
| `wishlists` | Customer-product favourites |
| `reviews` | Approved/moderated product reviews |

## Key Design Decisions

### Store-scoped data
Every table that belongs to a store has `store_id`. `Store::current()` in `app/Models/Store.php` is the single resolution point — swap this to tenant-aware in Phase 3.

### Attribute flexibility
Attributes are defined at store level, assigned to categories via `category_attributes` pivot. Products store values in `product_attribute_values`. Variants store their combination in `product_variants.attribute_values` (JSON). No code changes needed for new product types.

### Cart merge
`CartService::getOrCreateCart()` checks if a logged-in customer has a session cart and merges it. Called on every cart operation so it's always transparent.

### COD confirmation flow
`store_settings.cod_confirmation_required = 1` shows a warning to the customer. Admin sees a "Confirm COD" button per order in Filament. Sets `orders.cod_confirmed_at` and bumps status to `confirmed`.

### Auth guards
- `web` guard → `users` table → Filament admin
- `customer` guard → `customers` table → Storefront

### Scout search
`SCOUT_DRIVER=collection` in dev (no external service needed). Set `SCOUT_DRIVER=meilisearch` + `MEILISEARCH_HOST` in production. Run `php artisan scout:import "App\Models\Product"` after switching.

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

### Phase 2 — Payments & Couriers (NOT STARTED)
- [ ] SSLCommerz integration (card, net banking, Dutch-Bangla)
- [ ] bKash Mobile Banking API
- [ ] Pathao courier API (label generation, tracking webhook)
- [ ] Steadfast courier API
- [ ] RedX courier API
- [ ] Payment method pluggable pattern: `app/Services/Payment/{SSLCommerz,BKash}Service.php`
- [ ] Courier pluggable pattern: `app/Services/Courier/{Pathao,Steadfast,RedX}Service.php`

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

## Environment Variables

```env
# Core
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# DB (SQLite for dev, MySQL for prod)
DB_CONNECTION=sqlite
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=ecom_claude
# DB_USERNAME=ecom_user
# DB_PASSWORD=secret

# Search
SCOUT_DRIVER=collection          # dev: no external service
# SCOUT_DRIVER=meilisearch       # prod
# MEILISEARCH_HOST=http://127.0.0.1:7700

# Phase 2 — Payment (leave empty until Phase 2)
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
- [ ] Set `SESSION_DRIVER=database` (already in .env.example)
- [ ] Configure real SMS gateway for OTP (replace `Log::info` in `CustomerAuthController`)
- [ ] Set up queue worker (`QUEUE_CONNECTION=database`, `php artisan queue:work`)
- [ ] Configure `FILESYSTEM_DISK=s3` for media uploads (optional)
- [ ] Run `php artisan filament:upgrade` after any Filament package update
- [ ] Set up cron: `* * * * * php artisan schedule:run`
