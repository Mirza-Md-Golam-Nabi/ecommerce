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

## Freshness (implemented)
1. `Order::saved` and `Customer::created` dispatch the queued job `RefreshDailySummaries` (after
   commit) for the affected days: placement day + current/previous delivery day. The job is
   `ShouldBeUniqueUntilProcessing`, so many saves in one request queue one job.
2. `App\Services\Reports\DailySummaryBuilder::rebuild($day)` recomputes one day from raw orders
   (delete + insert in a transaction — idempotent, works on MySQL and SQLite).
3. Nightly 02:00 `reports:rebuild --days=3` safety net (`routes/console.php`). Manual:
   `php artisan reports:rebuild --from=2026-11-01 [--to=…]` or `--days=N`.
4. **A queue worker must run** (`composer run dev` starts one; production: Supervisor `queue:work`).
5. Days are local days: app timezone is `Asia/Dhaka` (D-032).

## Reports (Filament)

**Done (Phase 4)**
- Main dashboard: today's sales/profit, month sales/profit/margin with 14-day sparklines, open pipeline
  (pending → link to orders, to-buy → purchase list, out for delivery), 30-day revenue/profit chart.
- Reports page (`/admin/reports`, group "রিপোর্ট"), filters: from / until / group by day-week-month:
  headline stats (revenue, profit + margin + estimated-cost warning, delivered/placed + cancel rate,
  average order, lost sales, cost), revenue/profit chart, sales by period, top products,
  **unavailable products**, category sales, **area sales** ("অন্যান্য" texts flagged), cancel reasons.

**Later**
- Customer report: top, new vs returning, high cancel ratio.
- Price vs actual cost drift per product (estimated_cost accuracy).
- Hour/day heatmap, expenses & net profit, CSV export.

Report classes live in `app/Services/Reports` (`SalesReport`, `ProductReport`, `OrderReport`, `DateRange`),
return arrays, and are used by widgets in `app/Filament/Widgets` (`Reports/` = report page only).
