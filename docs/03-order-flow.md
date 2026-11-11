# 03 — Bag, Checkout & Order Lifecycle (COD, middleman)

## Customer journey

1. Browse category tree / search (guest allowed).
2. Add to bag from product card (qty stepper on the card) or product modal.
   Guest bag keyed by cookie token; merged on login/registration (`MergeGuestBag`:
   same product in both bags → the larger quantity wins, never summed).
3. Checkout requires login/register (name, phone, password). Phone accepted in any format
   (`+88`, spaces, dashes, Bangla digits) and stored normalized `01XXXXXXXXX`.
   After login the customer returns to the page they wanted (e.g. `/checkout`).
4. Address: **area dropdown** (active areas by name A→Z, then **"অন্যান্য"** last).
   - Area chosen → `area_id`.
   - "অন্যান্য" → `area_id = null`, required text `area_other`.
   - Then `address_line` text (house, road, landmark).
5. Delivery charge shown = `settings.default_delivery_charge`.
6. Place order → order page with order number. Total shown as **"আনুমানিক"** if bag
   contains weighted items.

## Quantity limits

Effective limit per product = `product.max_per_order` ?? `settings.default_max_qty_per_product` ?? unlimited.
Enforced in `SetBagItemQuantity` and again in `PlaceOrder`. UI stepper disables `+` at the limit.

## Line pricing formula (`LinePriceCalculator`)

```
fixed:     line_total = unit_price × (fulfilled_quantity ?? quantity)
weighted:  ordered_grams = quantity × nominal_weight_grams
           grams         = actual_weight_grams ?? ordered_grams
           line_total    = round(unit_price × grams / nominal_weight_grams)
unavailable: line_total = 0

line_cost (same shape) uses actual_unit_cost ?? estimated_unit_cost
```
Example: পেঁয়াজ ৳80 / 1 kg (nominal 1000 g), qty 2 → estimate ৳160. Weighed 2,080 g → ৳166.40.

Order totals are always recomputed by `OrderTotalsCalculator` after any item change,
never edited by hand (except `delivery_charge`, via its Action).

## Order status machine

```
pending ─► confirmed ─► purchasing ─► out_for_delivery ─► delivered
   │           │             │                │
   └───────────┴─────────────┴────────────────┴──► cancelled (with cancel_reason)
```

| Status | Meaning | Who |
|---|---|---|
| pending | placed, not yet verified | system |
| confirmed | admin phoned customer & verified | admin |
| purchasing | items being bought; appears on Purchase List | admin |
| out_for_delivery | handed to delivery person | admin |
| delivered | cash collected → `payment_status = paid`, `collected_amount` set | admin |
| cancelled | `cancel_reason`: `customer_request`, `unreachable`, `refused_at_door`, `all_unavailable`, `fake_order`, `other` | admin / customer (pending only) |

- Allowed transitions live only in `OrderStatus::canTransitionTo()`.
- All changes go through Actions and write an `order_events` row + fire `OrderChanged`.
- Before `delivered`: every weighted item that isn't unavailable must have `actual_weight_grams`.
- If **all** items become unavailable → order auto-cancelled with `all_unavailable`.

## Purchase List (admin daily workflow)

Filament page **"কেনাকাটার তালিকা"** (`App\Filament\Pages\PurchaseList`): items still to buy
(`OrderItem::toBuy()` = status `pending`, no actual cost, order `confirmed`/`purchasing`), summed per product:

| Product | Category | To buy (qty, ≈ kg for weighted) | Orders | Estimated cost | [কেনা হয়েছে] [পাওয়া যায়নি] |
|---|---|---|---|---|---|

- Printable (browser print). Subheading shows the estimated total spend.
- "নিশ্চিত অর্ডারগুলো কেনাকাটায় নিন" moves all `confirmed` orders to `purchasing`.
- **কেনা হয়েছে** → admin enters the actual unit cost → `ApplyPurchaseCosts` sets `actual_unit_cost`
  on every matching line; fixed-price lines become `fulfilled` (full quantity). Weighted lines stay
  `pending` until the actual weight is entered on the order page. The product leaves the list.
- **পাওয়া যায়নি** → `MarkItemUnavailable` on every matching line.

## Admin order page (Filament)

- Tabs: চলমান (default) / each status / সব, with counts. Navigation badge = pending orders.
- Header buttons follow the lifecycle (only the valid next step is shown) + বাতিল (reason required).
  "আরও" menu: delivery charge, admin note, invoice print (`/admin/orders/{order_number}/invoice`).
- Items table actions (until delivered/cancelled): আসল ওজন দিন (kg), কতটি কেনা হয়েছে,
  ক্রয়মূল্য দিন, পাওয়া যায়নি. Marking every item unavailable auto-cancels the order (`all_unavailable`).
- History tab lists `order_events` with who/when/what.
- Risk badge (`CustomerRisk`): ঝুঁকিপূর্ণ = blocked, or ≥2 cancellations and cancellations ≥ deliveries;
  বিশ্বস্ত = at least one delivery; otherwise নতুন কাস্টমার.

## Unavailable items

- `MarkItemUnavailable` sets item `status = unavailable`, `line_total = 0`, recomputes totals,
  logs event. **The row is never deleted.** Partial: `fulfilled_quantity` < quantity → `partial`.
- Customer sees on order page / invoice (templated, positive tone):

  > 🌿 দুঃখিত, আজ বাজারে মানসম্মত **"{product_name}"** পাওয়া যায়নি, তাই এটি পাঠানো সম্ভব হয়নি।
  > এর মূল্য আপনার বিল থেকে বাদ দেওয়া হয়েছে।

  Partial: `… "{product_name}" এর {ordered} টির মধ্যে {fulfilled} টি পাঠানো হয়েছে, বাকিটুকুর মূল্য বিল থেকে বাদ দেওয়া হয়েছে।`
- Reported in `04-reporting.md` (lost sales).

## Delivery charge

Default from settings at placement; admin may change on any non-final order via
`SetDeliveryCharge` (logged with from/to and user).

## PlaceOrder steps (one DB transaction)

1. Reject if customer blocked or has ≥ `max_pending_orders_per_customer` pending orders.
2. Load bag items with products; reject inactive/unavailable products (tell user which).
3. Enforce quantity limits.
4. Snapshot price/cost/name/unit/category into `order_items` (status `pending`).
5. Compute totals; create order (`pending`, `placed_at`), address snapshot.
6. `order_events` `placed`; update customer counters; empty bag.
7. `OrderPlaced` dispatched after commit.

## Fake order control (no OTP)
Rate limits (register 5/h/IP, order 5/h/customer), pending-order cap, risk badge in admin
(delivered vs cancelled count, other orders with same phone/IP), phone confirmation
before `confirmed`, block customer.
Phone normalization: strip `+88`, spaces, dashes → must match `^01[3-9]\d{8}$`.
