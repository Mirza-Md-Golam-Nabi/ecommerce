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
