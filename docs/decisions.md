# Architecture Decision Log

Format: ID — Date — Decision — Why. Append new decisions; mark replaced ones "Superseded by D-xxx".

**D-001 — 2026-11-11 — Laravel 13 + Filament 5 + MySQL.**
~~Blade/Livewire storefront~~ Superseded (storefront part) by D-010.

**D-002 — 2026-11-11 — Separate `customers` table & `customer` guard; admins in `users`.**
Phone login vs email login; hard isolation from admin panel.

**D-003 — 2026-11-11 — Money as unsigned BIGINT poisha; weight as INT grams.**
No float rounding errors.

**D-004 — 2026-11-11 — Product variants.** Superseded by D-011.

**D-005 — 2026-11-11 — Snapshot price, cost, name, category, area & address into orders.**
Historic orders/reports never change when catalog changes.

**D-006 — 2026-11-11 — Stock reservation & stock ledger.** Superseded by D-013.

**D-007 — 2026-11-11 — Revenue/profit recognized at `delivered_at`.**
COD: cash exists only after delivery.

**D-008 — 2026-11-11 — Reports read daily summary tables (queued refresh + nightly rebuild).**

**D-009 — 2026-11-11 — No OTP; fraud control via rate limits, pending cap, risk badge, phone confirmation, blocking.**

**D-010 — 2026-11-11 — Storefront = Inertia + React + TypeScript + Tailwind; Filament for admin only.**
Chaldal-like no-reload UX (modal product, slide-over bag, optimistic qty) is natural in React;
Inertia avoids a separate API now; Actions stay reusable for a future `/api/v1`.

**D-011 — 2026-11-11 — No variants: each pack size is its own product; `sale_type` fixed | weighted.**
Grocery-only catalog, like Chaldal. Simpler schema and admin.

**D-012 — 2026-11-11 — Unlimited-depth category tree (parent_id + materialized path), products only in leaf categories, one category per product; flat `/{slug}` URLs with shared slug namespace.**
Chaldal navigation model (non-leaf → tiles, leaf → products).

**D-013 — 2026-11-11 — Middleman model: no stock tracking. Product `is_available` toggle; actual purchase cost entered per order item via Purchase List.**
Business buys from local shops per order. Own stock may be added later (`track_stock`).

**D-014 — 2026-11-11 — Weighted items: price per nominal unit; admin enters actual weight; line price = unit_price × grams / nominal_grams.**

**D-015 — 2026-11-11 — Unavailable items are kept (status `unavailable`), excluded from bill, shown to customer with a polite templated Bangla message, and reported as lost sales.**

**D-016 — 2026-11-11 — Names in Bangla only (single column); `search_keywords` for English/Banglish search; English slugs.**

**D-017 — 2026-11-11 — Areas: admin-managed dropdown + "অন্যান্য" always last (area_id null + free text); single city.**

**D-018 — 2026-11-11 — Delivery charge: settings default, admin-editable per order (logged). No minimum order / free delivery for now.**

**D-019 — 2026-11-11 — Per-product max quantity (`max_per_order`) falling back to settings default; null = unlimited.**

**D-020 — 2026-11-11 — Admin-selectable color themes (presets in `config/themes.php`, CSS variables, default Emerald & Gold).**

**D-021 — 2026-11-11 — Scope v1 excludes coupons, time slots, favourites, flash sales, multi-language, multi-city.**

**D-022 — 2026-11-11 — No manual ordering: areas, categories and products are listed by `name` A→Z (`sort_order` removed).**
Less admin work. Only exceptions: `categories.home_sort_order` (home page "popular categories") and `product_images.sort_order` (drag-to-reorder gallery).

**D-023 — 2026-11-11 — Edit & Delete buttons are icon-only (with tooltip) across the whole project.**
Cleaner, compact tables and lists. Enforced globally for Filament in `AppServiceProvider`.
