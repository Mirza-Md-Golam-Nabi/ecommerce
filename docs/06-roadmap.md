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
- [ ] StoreLayout: header, search, category sidebar, bag button
- [ ] Home: hero + popular categories grid
- [ ] `/{slug}` resolver: category (tiles or product grid) / product page
- [ ] ProductCard with qty stepper; ProductModal
- [ ] Search
- [ ] Guest bag + BagPanel (optimistic)

## Phase 3 — Customers & orders
- [ ] customers + `customer` guard, register/login (phone)
- [ ] Addresses with area dropdown + "অন্যান্য"
- [ ] Checkout + `PlaceOrder`; quantity limits
- [ ] My orders + order detail with unavailable messages
- [ ] Filament OrderResource: status actions, delivery charge edit, item weight/qty/unavailable, events timeline, risk badge, invoice print
- [ ] Purchase List page + `ApplyPurchaseCosts`

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
