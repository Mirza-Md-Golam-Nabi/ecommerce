# 04 — Reporting & Analytics

## Principle
Raw `orders`/`order_items` are the source of truth. Report pages read
**pre-aggregated daily summary tables** (derived, rebuildable with
`php artisan reports:rebuild --from=YYYY-MM-DD`).

## Metric definitions (use these everywhere)

| Metric | Formula | Date |
|---|---|---|
| Orders placed | count | `placed_at` |
| Booked sales | Σ grand_total of non-cancelled orders | `placed_at` |
| **Revenue** | Σ `subtotal` of delivered orders | `delivered_at` |
| Delivery income | Σ `delivery_charge` of delivered | `delivered_at` |
| **Cost (COGS)** | Σ `cost_total` of delivered | `delivered_at` |
| **Gross profit** | Revenue − COGS | `delivered_at` |
| Margin % | Gross profit ÷ Revenue × 100 | |
| Profit is estimated? | any delivered order with `cost_is_final = false` → show "আনুমানিক" badge | |
| **Lost sales** | Σ estimated value of unavailable / partial-missing items | order `placed_at` |
| Fulfilment rate | fulfilled lines ÷ all lines | |
| Cancel rate (+ by reason) | cancelled ÷ placed | `placed_at` |
| AOV | Revenue ÷ delivered orders | |

Profit is recognized at delivery (COD — cash only exists then).
Later: `expenses` table (rider salary, transport, ads) → net profit.

## Summary tables

### daily_sales_summaries (PK `date`)
`orders_placed, orders_delivered, orders_cancelled, booked_sales, revenue, delivery_income,
cogs, gross_profit, estimated_cost_orders, lost_sales, unavailable_lines, items_delivered,
new_customers, updated_at`

### daily_product_sales (PK `date, product_id`)
`category_id, quantity_sold, grams_sold, revenue, cogs, profit, unavailable_count, lost_value`
Indexes: `(product_id, date)`, `(category_id, date)`.

Area-wise and cancel-reason reports query `orders` directly with indexes (volume is small).

## Freshness
1. `OrderPlaced`/`OrderChanged` → queued `RefreshDailySummaries` recomputes only affected
   dates with idempotent `INSERT … ON DUPLICATE KEY UPDATE`.
2. Nightly `reports:rebuild --days=3` safety net.
3. Dashboard "today" widgets query raw tables, cached 5 min.

## Reports (Filament → "রিপোর্ট" group)

**Phase 1**
- Dashboard: today/7d/30d revenue, profit, orders by status, pending count, today's purchase list size.
- Sales over time (day/week/month).
- Profit report (revenue, cost, profit, margin, estimated badge).
- Top products (qty / revenue / profit).
- **Unavailable products report:** how often each product was unavailable, lost value → supplier/market decisions.
- Order status & cancel-reason breakdown.

**Phase 2**
- Category-wise sales/profit.
- **Area-wise** orders & revenue, incl. top "অন্যান্য" texts (to promote into real areas).
- Customer report: top, new vs returning, high cancel ratio.
- Price vs actual cost drift per product (estimated_cost accuracy).

**Phase 3**
- Hour/day heatmap, expenses & net profit, CSV export.

Report classes live in `app/Services/Reports`, return arrays/DTOs, used by Filament pages & widgets.
