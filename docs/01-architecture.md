# 01 — Architecture

## 1. Big picture

```
                ┌──────────────────────────── Laravel app ────────────────────────────┐
 Customer ──►   │  Storefront: Inertia controllers → React pages (resources/js)  ─┐    │
                │                                                                 ├─► Actions (writes) ─► Models ─► MySQL
 Admin    ──►   │  Filament panel (/admin)                                       ─┘    Services (reads/reports)
                │                                     Events ─► queued Listeners (report summaries)   │
                │  Scheduler: nightly reports rebuild                                                 │
                └──────────────────────────────────────────────────────────────────────────────────────┘
```

**Core rule:** UI layers (Filament resources, Inertia controllers) are *thin*.
They validate and call an **Action** (write) or a **Service** (read). If we
later build a separate React/mobile app, we add `/api/v1` JSON controllers that
call the same Actions/Services — nothing is rewritten.

## 2. Users & guards

| Who        | Table       | Guard      | Login            | Where             |
|------------|-------------|------------|------------------|-------------------|
| Admin/staff| `users`     | `web`      | email + password | Filament `/admin` |
| Customer   | `customers` | `customer` | phone + password | Storefront        |

Staff roles (later): `role` enum on `users` + Policies.

## 3. Folder structure

```
app/
  Actions/
    Cart/        AddToBag, SetBagItemQuantity, MergeGuestBag
    Customer/    RegisterCustomer, SaveAddress
    Order/       PlaceOrder, ChangeOrderStatus, CancelOrder,
                 MarkItemUnavailable, SetItemFulfillment (weight/qty),
                 SetDeliveryCharge, ApplyPurchaseCosts
    Catalog/     (thin; Filament handles most CRUD)
  Enums/         OrderStatus, OrderItemStatus, SaleType, CancelReason, PaymentStatus, OrderEventType
  Events/        OrderPlaced, OrderChanged
  Listeners/     RefreshDailySummaries (queued)
  Models/
  Services/
    Catalog/     CategoryTree (cached), ProductSearch
    Pricing/     LinePriceCalculator, OrderTotalsCalculator
    Reports/     SalesReport, ProfitReport, ProductReport, UnavailableReport, AreaReport
    PurchaseList.php
  Settings/      StoreSettings (typed accessor over `settings` table, cached)
  Support/       Money, PhoneNumber, Theme
  Filament/
    Resources/   Category, Product, Order, Customer, Area
    Pages/       StoreSettings, PurchaseList, Reports/*
    Widgets/
  Http/
    Controllers/Storefront/  Home, Catalog (slug resolver), Product, Search, Bag, Checkout, Account, Orders
    Middleware/HandleInertiaRequests.php   (shares: auth customer, bag summary, category tree, theme, settings)
    Requests/
config/themes.php           preset color themes (see 08-storefront-ux.md)
resources/js/
  app.tsx
  Layouts/StoreLayout.tsx   persistent layout: header, sidebar tree, bag panel
  Pages/                    Home, Category, Product, Search, Checkout, OrderPlaced, Account/*, Orders/*
  Components/               ProductCard, QtyStepper, CategoryTile, CategorySidebar, BagPanel, ProductModal, ...
  hooks/useBag.ts           optimistic bag state
docs/
```

## 4. Routing (flat URLs like Chaldal)

```
GET  /                      Home
GET  /search?q=             Search results
GET  /bag, POST/PATCH /bag/items      Bag (JSON-ish Inertia partial responses)
GET  /checkout, POST /checkout
GET  /orders, /orders/{order_number}
GET  /login, /register ...
GET  /{slug}                Catalog resolver — MUST be the last route
```

`/{slug}` resolution order: category slug (from cached map) → product slug →
404. Categories and products share **one slug namespace**: a validation rule
(`UniqueStoreSlug`) checks both tables plus a reserved-word list
(`admin, bag, checkout, login, register, logout, orders, search, account, api, storage, build`).

Product detail: when opened from a listing it shows as a **modal** over the list
(client-side, URL updated to `/{product-slug}` via history), and a direct visit
renders the full Product page. See `08-storefront-ux.md`.

## 5. Settings

`settings` table (key → JSON value) wrapped by `App\Settings\StoreSettings`
with typed getters, cached forever, cache busted on save. Edited in Filament
page **Store Settings**. Keys: `store_name`, `store_phone`, `theme`,
`default_delivery_charge`, `default_max_qty_per_product`, `max_pending_orders_per_customer`.

## 6. Future: own stock

When own inventory starts: add `products.track_stock`, `stock`, and a
`stock_movements` ledger; `PlaceOrder` decrements only for tracked products.
Nothing in the current design blocks this.
