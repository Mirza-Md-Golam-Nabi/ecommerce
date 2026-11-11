# Project Design Docs — Index

> **For AI agents:** Read this file first, then the doc relevant to your task.
> These docs are the source of truth for design decisions. If code and docs
> disagree, ask the user — do not silently diverge. When a decision changes,
> update the doc **and** add an entry to `decisions.md`.

## What we are building

A **Chaldal-style online grocery** for **one city** in Bangladesh.

- **Middleman model (no stock):** the business does not hold inventory. Each
  confirmed order's items are bought from local shops/markets, then delivered.
  Own-stock management may come later — do not build it now.
- Catalog: deep category tree (e.g. খাবার → রান্না → মসলা). Non-leaf category
  pages show sub-category tiles; leaf category pages show products.
- **Each pack size is a separate product** (no variants). Two sale types:
  `fixed` (packaged, fixed price) and `weighted` (vegetables/fish/meat — final
  weight & price set by admin at purchase/delivery).
- **Product names in Bangla only** (single `name` column). Slugs are English.
- **Cash on Delivery only.** No online payment.
- Customer registration: **name + phone + password**, no OTP. Guests can fill
  the bag; login is required at checkout.
- Delivery area: dropdown of admin-managed areas + **"অন্যান্য"** (always last)
  with free-text area name; then free-text address line.
- Delivery charge: admin default in settings, editable per order.
- Items not found in the market are marked **unavailable** (kept in DB, shown to
  the customer with a polite message, excluded from the bill, reported).
- Strong **reporting**: sales, profit (actual purchase cost), top products,
  unavailable/lost sales, area-wise sales.
- Storefront must look **premium/elegant**; admin can switch among preset
  **color themes** from settings.

## Stack

| Layer        | Choice                                         |
|--------------|------------------------------------------------|
| Backend      | Laravel 13 (PHP 8.3+)                          |
| Admin panel  | Filament 5 (`/admin`)                          |
| Storefront   | **Inertia.js v3 + React 19 + TypeScript + Tailwind CSS v4** |
| Database     | MySQL 8 (InnoDB, `utf8mb4_unicode_ci`)         |
| Cache/Queue  | `database` driver now → Redis later            |
| Tests        | Pest 4                                         |

## Doc map

| File | Contents |
|------|----------|
| [01-architecture.md](01-architecture.md) | Layers, folders, guards, routing, Inertia setup |
| [02-database.md](02-database.md) | All tables, columns, indexes, money rules |
| [03-order-flow.md](03-order-flow.md) | Bag → checkout → order lifecycle, purchase list, weighted items, unavailable items |
| [04-reporting.md](04-reporting.md) | Report definitions, summary tables |
| [05-performance.md](05-performance.md) | Speed rules |
| [06-roadmap.md](06-roadmap.md) | Phased checklist |
| [07-conventions.md](07-conventions.md) | Coding rules |
| [08-storefront-ux.md](08-storefront-ux.md) | Chaldal-style pages, components, theme system |
| [decisions.md](decisions.md) | Architecture Decision Log |
