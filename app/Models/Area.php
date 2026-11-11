<?php

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Delivery area within the city. The storefront dropdown lists active areas
 * by name (A→Z) and always appends "অন্যান্য" (other) last — that is not a row.
 */
#[Fillable(['name', 'is_active'])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Area>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('name');
    }
}
