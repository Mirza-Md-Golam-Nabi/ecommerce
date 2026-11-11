<?php

namespace App\Models;

use App\Services\Cart\CurrentBag;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shopping bag. A guest bag is identified by `token` (encrypted cookie); a logged-in customer
 * has one bag with `customer_id`. Use App\Services\Cart\CurrentBag to resolve the current bag.
 *
 * @property int $id
 * @property string $token
 * @property int|null $customer_id
 */
#[Fillable(['token', 'customer_id'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory, Prunable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
        ];
    }

    /**
     * Guest bags untouched for longer than the cookie lifetime are deleted by `model:prune` (daily).
     *
     * @return Builder<Cart>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('customer_id')
            ->where('updated_at', '<', now()->subMinutes(CurrentBag::COOKIE_MINUTES));
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
