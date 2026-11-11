# 08 — Storefront UX (Chaldal-style) & Theme System

Reference analysed: chaldal.com (Nov 2026). We copy the **interaction model**, not its
branding, images or text.

## Layout (desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│ Logo   [🔍 পণ্য খুঁজুন ...................]        লগইন   🛍 ব্যাগ │  header (primary color)
├───────────────┬──────────────────────────────────────────────────────┤
│ Category tree │  Breadcrumb: খাবার › রান্না › মসলা                    │
│ ▸ খাবার        │                                                      │
│   ▾ রান্না      │  [ content: tiles OR product grid ]                 │
│     • মসলা     │                                                      │  ┌──────┐
│     • চাল      │                                                      │  │ 3 টি │ floating bag
│ ▸ পরিষ্কার     │                                                      │  │ ৳450 │ button (accent)
└───────────────┴──────────────────────────────────────────────────────┘  └──────┘
```
Mobile: sidebar becomes a slide-in drawer (hamburger); bag becomes a bottom bar.

## Pages

| Page | Content |
|---|---|
| **Home** | Hero banner (store promise: "বাজারের তাজা পণ্য, দরজায় পৌঁছে দিই · ক্যাশ অন ডেলিভারি"), then **জনপ্রিয় ক্যাটাগরি** grid (`show_on_home`). **No product lists on home.** |
| **Category (non-leaf)** | Breadcrumb + grid of sub-category tiles (image + name). |
| **Category (leaf)** | Breadcrumb + product grid (sort: default / দাম কম→বেশি / বেশি→কম / জনপ্রিয়), infinite scroll. |
| **Product** | Quick-view modal when clicked in a grid (URL unchanged; closes on Esc/backdrop/navigation; has a link to the full page); full page on direct visit to `/{slug}`. Gallery, name, unit_label, price (+ strike price), description, qty stepper / "ব্যাগে যোগ করুন". Weighted: note "চূড়ান্ত দাম ওজন অনুযায়ী নির্ধারিত হবে". |
| **Search** | Same grid as leaf category. |
| **Bag panel** | Slide-over from right: items with stepper, remove, subtotal, delivery charge, total (আনুমানিক if weighted), "অর্ডার করুন". |
| **Checkout** | Login/register gate → name, phone, area dropdown (+ অন্যান্য last), address line, note → summary → place order. |
| **Order placed** | Order number, thanks, what happens next (we'll call to confirm). |
| **My orders / Order detail** | Status timeline, items; unavailable items shown with the polite message, struck-through price. |

## ProductCard

```
┌────────────────────┐
│      [image]       │  -12% badge if compare_at_price
│ রাঁধুনি হলুদ গুঁড়া   │  name (2 lines max)
│ ২০০ গ্রাম            │  unit_label (muted)
│ ৳৮৫  ৳৯৫            │  price (bold) + strike
│ [ ব্যাগে যোগ করুন ]   │  → after add becomes  [ − 2 + ]
└────────────────────┘
```
States: unavailable → greyed, "এখন পাওয়া যাচ্ছে না", no button. At max limit → `+` disabled with tooltip.

## Bag behaviour
- Guests can fill the bag (cookie `bag_token`, 30 days). Login is required only at checkout.
- `BagContext` (`useBag()`): quantities by product id from the shared `bag` prop; the UI updates
  instantly, and the **absolute** quantity per product is sent with `PUT /bag/items/{id}` after a
  300 ms debounce. When nothing is pending, the server summary wins. On error: toast with the
  server's Bangla message + reload of the `bag` prop.
- Bag panel lists items from `GET /bag`; quantities/totals come from the context so it reacts instantly.
  Items no longer orderable stay listed with a message and are excluded from the total.
- Delete in the bag is an icon button (trash) with tooltip — conventions rule 16.

## Numbers
- Customer-facing numbers use Bangla digits: money via `formatMoney()` (`৳১,২৫০.৫`), counts via
  `toBanglaDigits()` (`resources/js/lib/format.ts`). PHP side: `App\Support\BanglaNumber`.

## Visual style
- Font: **Hind Siliguri** (Bangla + Latin), weights 400/500/600/700.
- Rounded cards (`rounded-2xl`), soft shadows, generous whitespace, subtle hover lift.
- Product images on white square with padding; consistent aspect ratio.
- Micro-interactions: add-to-bag pulse, slide-over bag, skeleton loaders.

## Theme system (admin-selectable)

`config/themes.php` holds presets; `settings.theme` stores the chosen key
(default `emerald_gold`). Filament **Store Settings** shows a select with color
swatches preview.

| key | Name | primary | accent | surface | card | success | text |
|---|---|---|---|---|---|---|---|
| `emerald_gold` (default) | Emerald & Gold | `#0F3D2E` | `#C9A227` | `#FAF7F0` | `#FFFFFF` | `#2E7D5B` | `#1C1C1C` |
| `midnight_saffron` | Midnight & Saffron | `#14213D` | `#F59E0B` | `#FFF9F0` | `#FFFFFF` | `#16A34A` | `#111827` |
| `plum_champagne` | Plum & Champagne | `#3B1E3F` | `#E6C79C` | `#FBF8F5` | `#FFFFFF` | `#3F8F5F` | `#1F1720` |

Each preset also defines `primary_fg` and `accent_fg` (readable text color on that background;
must pass WCAG AA contrast) plus `muted` and `border`.

**Mechanism**
1. `app.blade.php` prints `<style>:root{--c-primary:#0F3D2E; --c-accent:… }</style>` from
   `Theme::current()` (cached) → correct colors on first paint.
2. Tailwind maps tokens to those variables (`--color-primary: var(--c-primary)` in `@theme`),
   so components use `bg-primary`, `text-accent`, `bg-surface`, etc.
3. Changing theme in admin busts the settings cache; next page load uses new colors.
   No rebuild needed. Adding a theme = adding one array entry in `config/themes.php`.
