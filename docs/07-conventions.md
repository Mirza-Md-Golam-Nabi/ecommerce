# 07 — Coding Conventions

1. Business writes only in `app/Actions`; reads/calculations in `app/Services`.
   Filament resources, Inertia controllers, React pages contain no business rules.
2. Actions: `final class`, one public `handle()`, no `request()`/session/redirect inside;
   multi-table writes in `DB::transaction()`.
3. Money in poisha (int), weight in grams (int). Never floats. Format only for display.
4. Status/type values are backed enums in `app/Enums` (TitleCase keys), cast on models.
5. Order status/items/delivery charge change only through their Actions; every change writes `order_events`.
6. Order totals only via `OrderTotalsCalculator`; line amounts only via `LinePriceCalculator`.
7. Never delete order items — mark `unavailable`.
8. Customer-facing text is Bangla. Code, slugs, DB column names are English.
9. React: TypeScript, function components, props typed from shared `resources/js/types`.
   Colors only via theme tokens (`bg-primary`, `text-accent` …) — **never hard-coded hex in components**.
10. Validation in FormRequests; slugs via `UniqueStoreSlug`.
11. Eager load; no queries in loops.
12. Every Action has a Pest feature test (happy path + main failure).
13. Never edit a migration that ran in production.
14. Run `vendor/bin/pint --dirty` before finishing.
15. Changing a design rule → update the relevant `docs/*.md` and `docs/decisions.md`.

## UI rules

16. **Edit & Delete are icon-only everywhere** (admin panel and storefront): pencil icon for edit,
    trash icon for delete — no text label on the button. Always add a tooltip + accessible label
    (`এডিট` / `মুছুন`) so the meaning is clear on hover and for screen readers.
    - Filament: configured globally in `App\Providers\AppServiceProvider::configureFilamentActions()`
      (`EditAction` / `DeleteAction` → `iconButton()` + tooltip). Do not override this per resource.
    - React storefront: use one shared icon-button component for edit/delete (e.g. address list, bag item remove).
    - Other actions (Create, Save, Restore, bulk actions) keep their text labels.

## DRY — no duplicated code

17. **Never duplicate logic, markup or constants.** If something is needed in a second place, move it
    to a central helper first and use it from both places. Before writing new code, check the
    registry below (and `app/Support`, `app/Filament/{Forms,Tables,Infolists,Concerns,Support}`,
    `resources/js/{Components,hooks,lib}`). When you add a new central helper, add it to this table.
18. Values shared by backend and frontend are defined **once on the server** and sent as props
    (e.g. sort options, the "other area" value) — never re-typed in TypeScript.

### Central helper registry

| Need | Use |
|---|---|
| Money (poisha ↔ taka, display) | PHP `App\Support\Money`; JS `formatMoney()` in `resources/js/lib/format.ts` |
| Weight display (grams → kg) | PHP `App\Support\Weight::format()`; JS `formatWeight()` |
| Bangla digits | PHP `App\Support\BanglaNumber`; JS `toBanglaDigits()` / `formatNumber()` |
| Phone normalize / validate | `App\Support\PhoneNumber`, rule `BangladeshiPhone`, request trait `Http\Requests\Concerns\NormalizesPhone` |
| Slug format + uniqueness | `App\Rules\UniqueStoreSlug` (`PATTERN` for routes, `REGEX` for forms, `RESERVED`) |
| Image URL | `App\Support\Media::url()` |
| Theme colors (any color in PHP/Blade/charts) | `App\Support\Theme::current()->colors` — never hard-coded hex |
| Order statuses in progress | `OrderStatus::active()` |
| Ordered grams / profit of an order | `OrderItem::orderedGrams()`, `Order::grossProfit()` |
| Storefront product listing order + pages | `Product::forStorefront($sort)` scope, `Product::SORT_OPTIONS`, `ProductCardResource::paginate()` |
| After customer login/registration | `App\Actions\Customer\CompleteCustomerLogin` |
| Margin text | `SalesReport::marginLabel()` |
| Filament money input / column / entry | `Filament\Forms\MoneyInput`, `Filament\Tables\MoneyColumn`, `Filament\Infolists\MoneyEntry` |
| Filament category path / risk badge column | `Filament\Tables\CategoryPathColumn`, `Filament\Tables\CustomerRiskColumn` |
| Filament: run an order Action (admin, notify, reload) | trait `Filament\Concerns\RunsOrderActions`, `Filament\Support\DomainAction` |
| Filament profit color | `Filament\Support\ProfitColor::for()` |
| React overlay (scroll lock + Esc) | hook `useModal()` |
| React totals / empty state / gallery / discount badge | `OrderTotals`, `EmptyState`, `ProductGallery`, `DiscountBadge` |
| React weighted-price note / COD text | `WeightedPriceNote` (`WEIGHTED_PRICE_NOTE`), `COD_NOTE` in `lib/copy.ts` |
| React category tile grid / product grid | `categoryGridClasses` (CategoryTile), `ProductGrid` |
| React icon-only edit/delete | `IconButton` |
| Feature tests: no Vite assets | handled once in `tests/TestCase::setUp()` |
