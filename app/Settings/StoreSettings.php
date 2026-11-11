<?php

namespace App\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Typed, cached access to admin-editable store settings (the `settings` table).
 *
 * Values missing from the database fall back to DEFAULTS. The whole set is
 * cached forever and the cache is cleared on every save.
 */
final class StoreSettings
{
    public const CACHE_KEY = 'store_settings';

    /**
     * @var array{
     *     store_name: string|null,
     *     store_phone: string|null,
     *     theme: string|null,
     *     default_delivery_charge: int,
     *     default_max_qty_per_product: int|null,
     *     max_pending_orders_per_customer: int,
     * }
     */
    public const DEFAULTS = [
        'store_name' => null,
        'store_phone' => null,
        'theme' => null,
        'default_delivery_charge' => 6000,
        'default_max_qty_per_product' => null,
        'max_pending_orders_per_customer' => 3,
    ];

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->resolved ??= Cache::rememberForever(self::CACHE_KEY, function (): array {
            /** @var array<string, mixed> $stored */
            $stored = Setting::query()->pluck('value', 'key')->all();

            return array_merge(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
        });
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Persist the given settings and clear the cache. Unknown keys are ignored.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->resolved = null;
    }

    public function storeName(): string
    {
        return $this->get('store_name') ?: config('app.name');
    }

    public function storePhone(): ?string
    {
        return $this->get('store_phone') ?: null;
    }

    public function theme(): ?string
    {
        return $this->get('theme') ?: null;
    }

    /**
     * Default delivery charge in poisha.
     */
    public function defaultDeliveryCharge(): int
    {
        return (int) $this->get('default_delivery_charge');
    }

    /**
     * Store-wide max quantity of one product per order; null means unlimited.
     */
    public function defaultMaxQtyPerProduct(): ?int
    {
        $value = $this->get('default_max_qty_per_product');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function maxPendingOrdersPerCustomer(): int
    {
        return (int) $this->get('max_pending_orders_per_customer');
    }
}
