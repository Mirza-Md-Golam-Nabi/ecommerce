# 05 — Performance Rules

Scale target now: ~1,000 products, one city. Design for 10× without rewrites.

## Database
- `Model::preventLazyLoading(! app()->isProduction())` — N+1 fails in dev.
- Eager load; `select()` only needed columns on listing queries.
- Every listing filter/sort hits an index (see 02-database.md); check with `EXPLAIN`.
- Category tree: one cached structure (`CategoryTree`), never queried per request.
  Descendants via materialized `path`, breadcrumb from cached tree (zero queries).
- Search: FULLTEXT (ngram) on `name, search_keywords` on MySQL; `LIKE` for 1-char queries and on MariaDB (no ngram parser).
- Paginate product grids (`simplePaginate` / infinite scroll with Inertia `WhenVisible`/merge props
  — verify feature in installed Inertia version).
- Reports read summary tables only.

## Caching
- `CategoryTree`, `StoreSettings` (incl. theme), active `areas`: `Cache::rememberForever` + bust on save.
- Home "popular categories": from cached tree.
- Dashboard widgets: 5 min.

## Inertia / frontend
- Persistent layout (`StoreLayout`) → sidebar, header, bag panel don't re-mount on navigation.
- Shared props kept tiny: customer `{id,name}`, bag `{count,total, items:{productId:qty}}`, theme tokens.
  Category tree shared but cached server-side; consider once/deferred props if supported.
- Bag updates are **optimistic** on the client, persisted with debounced requests; server
  response reconciles.
- Code-split pages (Vite dynamic imports, default in Inertia React setup).
- Images: queued WebP `thumb` (300px) & `medium` (800px), `loading="lazy"`, fixed aspect ratio (no layout shift).
- Theme CSS variables rendered server-side in `app.blade.php` → no color flash.

## Production checklist
`php artisan optimize`, `filament:optimize`, `composer install --no-dev -o`, OPcache on,
`APP_DEBUG=false`, `npm run build`, Redis for cache/session/queue + Supervisor queue worker,
MySQL slow query log on.
