# 06 — Roadmap

Mark `[x]` when done. Each item = one branch/PR with Pest tests.

## Phase 0 — Foundation
- [x] Laravel Boost installed
- [x] Design docs
- [x] Install Inertia v3 + React 19 + TypeScript + Tailwind v4
- [x] `Money`, `PhoneNumber` support classes; Enums
- [x] `settings` table + `StoreSettings` + Filament Store Settings page (incl. theme picker)
- [x] `config/themes.php` + theme CSS variables in root view
- [x] `preventLazyLoading`, admin user seeder (`ADMIN_EMAIL`/`ADMIN_PASSWORD` env)

## Phase 1 — Catalog (admin)
- [x] categories (tree, path, is_leaf) + Filament CategoryResource (tree-aware, leaf rule)
- [x] `CategoryTree` cached service (lineage, path labels, leaf/parent options)
- [x] products + product_images + Filament ProductResource (fixed/weighted fields, availability toggle, bulk available/unavailable)
- [x] `UniqueStoreSlug` rule + reserved words
- [x] areas + Filament AreaResource
- [x] Factories/seeders: realistic Bangla grocery tree (খাবার → রান্না → মসলা …), demo data skipped in production
- Note: images are resized in the browser on upload (max 1000px). Server-side WebP thumbnails are deferred (needs an image library — ask before adding).

## Phase 2 — Storefront
- [x] StoreLayout (persistent): header, search, collapsible category sidebar, mobile drawer, floating bag button / mobile bag bar
- [x] Home: hero + popular categories grid
- [x] `/{slug}` resolver: category (tiles or product grid with sort + infinite scroll) / product page
- [x] ProductCard with qty stepper; quick-view modal
- [x] Search (FULLTEXT ngram on MySQL, LIKE fallback)
- [x] Guest bag (cookie) + BagPanel (optimistic, debounced)

## Phase 3A — Customers & checkout (storefront)
- [x] customers + `customer` guard, register/login (phone, any format, Bangla digits), rate limits
- [x] Guest bag merged into customer bag on login/registration (`MergeGuestBag`, larger quantity wins)
- [x] Default address with area dropdown + "অন্যান্য" (saved at checkout, prefilled next time)
- [x] Checkout + `PlaceOrder` (blocked customer, pending-order cap, availability, quantity limits)
- [x] `LinePriceCalculator` / `OrderTotalsCalculator`; `ChangeOrderStatus` (transitions, counters, weights before delivery)
- [x] My orders + order detail (timeline, unavailable messages, customer cancel while pending)

## Phase 3B — Order management (admin)
- [x] Filament OrderResource: status tabs with counts, status actions, delivery charge edit, admin note, item weight/qty/cost/unavailable, events timeline, risk badge, invoice print
- [x] Item actions: `MarkItemUnavailable` (auto-cancel when nothing left), `SetItemFulfillment`, `SetItemCost`, `SetDeliveryCharge`
- [x] Filament CustomerResource (risk, counters, block switch)
- [x] Purchase List page + `ApplyPurchaseCosts` + "start purchasing" for confirmed orders
- Note: Filament tests use `callTableAction`/`assertTableActionHidden` (deprecated in v5 but working) — migrate to `TestAction` when convenient.

## Phase 4 — Reports
- [ ] Summary tables, listener, `reports:rebuild`
- [ ] Dashboard + Phase-1 reports (see 04-reporting.md)
- [ ] Phase-2 reports

## Later
- [ ] Coupons, flash sale / collections, favourites, delivery time slots
- [ ] SMS on status change
- [ ] Expenses → net profit
- [ ] Own stock (`track_stock`, stock ledger)
- [ ] Staff roles; `/api/v1` for mobile app
