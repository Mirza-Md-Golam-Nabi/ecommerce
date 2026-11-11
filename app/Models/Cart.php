<?php

namespace App\Models;

use App\Services\Cart\CurrentBag;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shopping bag. Guests are identified by `token` (stored in an encrypted cookie).
 * Use App\Services\Cart\CurrentBag to resolve the current visitor's bag.
 *
 * @property int $id
 * @property string $token
 */
#[Fillable(['token'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory, Prunable;

    /**
     * Guest bags untouched for longer than the cookie lifetime are deleted by `model:prune` (daily).
     *
     * @return Builder<Cart>
     */
    public function prunable(): Builder
    {
        return static::query()->where('updated_at', '<', now()->subMinutes(CurrentBag::COOKIE_MINUTES));
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
