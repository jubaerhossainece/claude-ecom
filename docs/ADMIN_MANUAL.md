# EcomClaude — Admin Operations Manual

This is a plain-language guide to running your store day-to-day in the admin panel. It does not assume any technical background. For what the system was built to do at a specification level, see `BRS.md` / `SRS.md` / `FRS.md` — this manual instead answers "how do I actually do X."

**Admin panel address:** `/admin` (e.g. `http://yourdomain.com/admin`)
**Login:** the email and password your administrator gave you (default demo login: `admin@store.test` / `password` — change this before going live).

---

## 1. The Dashboard

When you log in you land on the Dashboard, which gives you a snapshot of the store:

- **Sales tiles** — quick totals (revenue, order counts) for a glance at performance.
- **Operations tiles** — how many products are low on stock or out of stock right now.
- **Sales over time chart**, **order status breakdown chart**, **payment method breakdown chart**, **revenue by category chart**, **customer acquisition chart**.
- **Recent Orders** and **Recent Customers** lists — jump straight into the newest activity.
- **Top Selling Products** — your current best sellers.

Everything here is read-only reporting; the working screens are reached from the left-hand menu.

---

## 2. Store Settings

Menu: **Store Settings**. One page, six tabs. Click **Save** on any tab to apply changes.

### General
- **Store Name**, **Tagline**, **Description** — your store's identity shown across the site.
- **Support Phone / Support Email** — shown to customers who need help.
- **Default Language** — English or বাংলা.
- **Theme Color** — the storefront's primary brand color.

### Currency
- **Currency** (defaults to BDT) and **Currency Symbol** (defaults to ৳).

### Payment Methods
- **Cash on Delivery** — on/off switch for COD at checkout.
- **Require phone confirmation for COD** — if on, every COD order must go through the "Confirm COD" step (see §6) before you process it.
- **COD Confirmation SMS/Note** — the message shown/sent to the customer as part of that confirmation.
- **SSLCommerz** and **bKash** toggles — these switches exist, but as of this build **no live payment gateway is wired up yet** (see §11, "Known Gaps"). Turning them on will not actually process a card or bKash payment today.

### Delivery
- **Inside Dhaka charge** and **Outside Dhaka charge** — flat delivery fees.
- **Free delivery above ৳X** (0 disables it) — orders above this subtotal ship free.
- Note: actual per-order delivery pricing is primarily driven by **Delivery Zones** (configured elsewhere, not on this settings page); the "Outside Dhaka charge" here is the fallback used when no zone matches an order's address.

### Tax
- **Charge tax on orders** — master on/off switch.
- **Tax rate (%)** — only shown when tax is on. Applied to (subtotal − discount) at checkout.

### SEO
- **Meta Title** and **Meta Description** — default search-engine listing text for the storefront.

---

## 3. Building the Catalog

Set these up roughly in this order: **Attributes → Categories → Brands/Tags → Products.**

### 3.1 Attributes
Menu: **Attributes**. These are reusable characteristics like "Color" or "Size" that power product variants and storefront filters.

To add one: **New** → fill in **Name** (Slug auto-fills) → choose **Type**:
- *Text/Number* — free-value attribute (e.g. "Material"); you can set a **Unit** placeholder like "kg" or "cm".
- *Select/Multiselect* — pick-list attribute; add each choice under **Options** (a Label + Value per row, e.g. Red/red).

Three toggles matter a lot:
- **Use as filter on storefront** — lets customers filter product listings by this attribute.
- **Use to generate product variants** — marks it as one you can combine into variants (e.g. Size × Color).
- **Required when adding products** — forces this attribute to be filled in on every product.

**Sort Order** controls display order everywhere this attribute appears.

### 3.2 Categories
Menu: **Categories**. One level of sub-categories is supported (top-level category → child category, not deeper).

Fill in **Name**, **Description**, **Image** (thumbnail/icon) and **Banner** (wide hero image for the category page), set **Parent Category** if this is a sub-category, and toggle **Active** to publish it. Under **Attributes for This Category**, tick which attributes should appear as spec fields on products in this category. There's also a small **SEO** section (Meta Title/Description).

### 3.3 Brands
Menu: **Brands**. Fill in **Name**, **Description**, **Website**, **Logo**, toggle **Active**. Click into a brand (**View**) to see a read-only list of every product under that brand.

### 3.4 Tags
Menu: **Tags**. Just a **Name** — free-form labels for merchandising/search, not hierarchical like categories.

### 3.5 Products
Menu: **Products**. This is the biggest form in the system — six tabs:

1. **Basic Info** — Name, Slug, Category (required), Brand, Status (Draft/Active/Inactive/Archived — only *Active* is visible to customers), Unit of Sale (piece/kg/gram/litre/etc.), Featured toggle, Short/Long Description, Tags.
2. **Pricing & Inventory** — Base Price (required), Sale Price, Cost Price (internal only, used for inventory valuation, never shown to customers), SKU, **Track Inventory** toggle, **Allow Backorder** toggle, **Low Stock Threshold** (default 5 — this is what triggers the low-stock warning), Weight, Dimensions.
   - **You cannot type a stock quantity directly on this form.** Stock is set per warehouse via the **Manage Stock** button on the product list (below) — this is intentional, so stock always stays tied to a specific location.
3. **Images** — one Thumbnail (main listing photo) plus a reorderable Gallery.
4. **Variants** — add one row per variant (e.g. "Red / XL"): its attribute values, its own SKU/barcode, optional price override (falls back to the product price if left blank), optional weight override, its own image, and an Active toggle.
5. **SEO** — Meta Title/Description.

**Working with the product list:**
- **Duplicate** — clones a product (new slug, cleared SKU, resets to Draft, copies images and variants) — a fast way to create near-identical products.
- **Manage Stock** — shown only when Track Inventory is on. Lists every warehouse × variant combination with its current **On Hand** quantity (editable) and **Reserved** quantity (read-only — held by unshipped orders). Type a new on-hand number and an optional reason, save, and it's logged as an "adjustment" in the inventory ledger.
- **Report Damaged** — shown only when Track Inventory is on. Pick a warehouse/variant, enter a quantity and a required reason (e.g. "Water damage during storage"); this writes off stock and logs it as "damaged" in the ledger.
- **Import / Export** (top of the list page) — bulk-load or download products as CSV/XLSX. Import matches existing products by **SKU first, then Slug**, so re-uploading the same file updates rather than duplicates. A "Stock Quantity" column in the import file sets on-hand stock directly in your **default warehouse only**, and does *not* create a ledger entry the way Manage Stock does.

**How "low stock" and "out of stock" are decided:** available quantity = on-hand − reserved. Out of stock = available ≤ 0. Low stock = available is at or below the product's Low Stock Threshold (but still > 0).

---

## 4. Inventory & Warehouses

### 4.1 Warehouses
Menu: **Warehouses**. Add Name, Address, and mark **Active**. Toggling **Default warehouse** on one automatically un-defaults any other — only one warehouse can be default at a time, and it's the one product imports and (implicitly) fulfillment lean on.

### 4.2 Stock Alerts
Menu: **Stock Alerts**. A monitoring screen only — lists every product currently low or out of stock, with a **Manage Stock** shortcut into the Products list for that item. No editing happens on this page itself.

### 4.3 Inventory Report
Menu: **Inventory Report**. Four summary tiles (On-Hand Value in ৳, Total On-Hand Units, Reserved Units, Low/Out of Stock counts) plus a per-warehouse breakdown table. **Download CSV** exports that same per-warehouse table (Warehouse, Products, On Hand, Reserved, Value) — it's a warehouse-level summary, not a per-SKU export.

### 4.4 Inventory Movements
Menu: **Inventory Movements**. A read-only ledger of every stock change the system has ever made, with type, product, warehouse, the quantity change, the resulting balance, the linked order (if any), a reason, and who did it. You cannot create entries here — every row is written automatically by another action:

| Movement type | Written automatically when... |
|---|---|
| **Sale** | An order's status is set to *Shipped* — this is the moment stock actually leaves on-hand |
| **Return** | A *shipped* order is cancelled/returned — stock is added back |
| **Manual Adjustment** | You use **Manage Stock** on a product |
| **Damaged** | You use **Report Damaged** on a product |

(Restock, Transfer In, and Transfer Out movement types exist in the system but currently have no button anywhere that triggers them — there is no warehouse-to-warehouse transfer screen yet.)

**The reserve → commit → release model, in plain terms:** placing an order *holds* stock (Reserved) without touching On Hand. Shipping the order is what actually removes it from On Hand. Cancelling before shipment just releases the hold — nothing was ever deducted. This is why On Hand never drops the moment an order comes in, only once it ships; it's what stops unconfirmed COD orders from causing false "out of stock" situations.

---

## 5. Orders

Menu: **Orders**. This is the busiest day-to-day screen.

### Order status flow
```
Pending → Confirmed → Processing → Shipped → Delivered
                                        ↘
                                  Cancelled / Returned  (can happen from almost any state)
```

| Status | Meaning |
|---|---|
| Pending | Just placed, nothing actioned yet |
| Confirmed | Customer/order confirmed (see COD confirmation below) |
| Processing | Being prepared for shipment |
| Shipped | Handed to courier — **this is when stock is actually deducted** |
| Delivered | Received by customer |
| Cancelled | Order called off (stock is released/restored automatically) |
| Returned | Sent back after delivery (stock is restored automatically) |

Payment methods: **Cash on Delivery, SSLCommerz, bKash** (only COD is actually live today — see §11). Payment status: **Unpaid, Paid, Refunded, Failed**.

Every status change — whether you set it manually or via a button below — notifies the customer and is written to the order's status history automatically.

### Day-to-day actions (list page)
Quick tabs at the top: **All / Pending / Confirmed / Shipped / Delivered / Cancelled** (Pending shows a live count badge). Filters are available for status, payment method/status, customer, product, date range, and amount range.

- **Confirm COD** — appears on Pending COD orders that haven't been confirmed yet. One click: stamps who confirmed it and when, and moves the order to Confirmed. Use this after you've called the customer to verify they still want the order.
- **Cancel Order** — available on any order that isn't already Cancelled/Delivered/Returned. Requires a typed cancellation reason. Automatically releases/restores stock as described in §4.4.
- **Refund** — available once an order is Paid and not yet fully refunded. One click refunds the **full order total** and marks payment status Refunded. Does not change the order's shipping status.
- **Partial Refund** — available on Paid or partially-Refunded orders with money still refundable. Enter an amount (capped at what's left to refund); flips payment status to Refunded only once the full total has been refunded.
- **Invoice** — opens a printable invoice for the order in a new tab.

**Bulk actions** (select multiple orders): Mark Confirmed, Mark Shipped (this also triggers the automatic stock-commit for every order selected — use carefully, only once orders have genuinely left the warehouse).

### Editing an order
Opening **Edit** lets you change Status, Payment Status, Courier Name/Tracking ID, and Admin Notes directly, plus (for COD orders) the COD Confirmed At/By fields. Saving always logs "Updated by admin" in the order's history, even if nothing actually changed.

**Important:** refunding money and approving a return request are two separate, disconnected actions in this system (see §6) — approving a return does **not** automatically refund, and refunding does not require a return request to exist. If a customer needs both, do both steps.

---

## 6. Returns & Exchanges

Menu: **Return Requests**. Customers submit these from their account (against a specific item in a specific order); you only review and decide here — you cannot create one manually.

Status flow: **Pending → Approved → Completed**, or **Pending → Rejected**.
Types: Return or Exchange. Reasons customers can pick: Defective/damaged, Wrong item received, Not as described, Size/fit issue, No longer needed, Other.

Open a request (**View**) to see the order, customer, product, reason, the customer's written explanation, and any photos they attached.

- **Approve** — optional note to the customer, moves it to Approved. Does **not** touch the order, stock, or issue a refund by itself.
- **Reject** — requires a reason, moves it to Rejected.
- **Mark Completed** — available once Approved; use this once the item is physically back / the exchange is fulfilled. Still no automatic stock or refund action.

Because none of this is automatic, when you approve/complete a return you still need to, separately:
- Adjust stock via the product's **Manage Stock** action if the returned item goes back on shelf, and
- Issue **Refund**/**Partial Refund** on the original order in **Orders** if money needs to go back.

---

## 7. Customers

Menu: **Customers**. Customers can only be created by signing up on the storefront — you cannot add one manually here, only edit/view.

**Segments** (calculated live, not something you set):

| Segment | Rule |
|---|---|
| VIP | Lifetime spend ≥ ৳20,000 |
| New | Registered in the last 30 days |
| Inactive | Last order was more than 90 days ago |
| Returning | Placed more than 1 order |
| Active | Everyone else with an order history |

A customer's page shows their details, segment, lifetime spend, order count, and an **Admin Notes** field (internal only, never shown to the customer) — plus three read-only tabs: **Addresses** (saved delivery addresses), **Orders** (jump to any past order), **Wishlist** (products they've favourited). You can filter the customer list by segment, active status, and registration date range.

---

## 8. Quick Reference

**Order statuses:** Pending, Confirmed, Processing, Shipped, Delivered, Cancelled, Returned
**Payment methods:** Cash on Delivery, SSLCommerz, bKash
**Payment statuses:** Unpaid, Paid, Refunded, Failed
**Return statuses:** Pending, Approved, Rejected, Completed
**Product statuses:** Draft, Active, Inactive, Archived (only Active shows to customers)

---

## 9. Known Gaps (be upfront with customers about these)

As of this build, some settings exist on screen but aren't fully wired up yet — don't promise a customer something the system can't yet do:

- **SSLCommerz and bKash toggles are visual only** — no real online payment is processed. Only Cash on Delivery actually works end-to-end.
- **Courier fields (name/tracking ID) are free-text** — there's no live integration with Pathao/Steadfast/RedX to auto-generate labels or pull tracking updates.
- **OTP/notifications are logged, not texted** — in development, the OTP a customer needs is written to the server log, not sent as a real SMS. This must be replaced with a real SMS gateway before go-live.
- **No warehouse-to-warehouse transfer screen** — the ledger has the movement types for it, but there's no button to actually move stock between warehouses yet.
- **No loyalty/rewards program, live chat, admin audit log, or admin two-factor login** — these were explicitly deferred, not silently dropped.

If any of these matter for a specific sale, flag it before promising the feature is live.
