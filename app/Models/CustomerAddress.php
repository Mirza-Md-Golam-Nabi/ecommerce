<?php

namespace App\Models;

use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery address. `area_id` null = "অন্যান্য" with the area name typed in `area_other`.
 *
 * @property int $id
 * @property int $customer_id
 * @property string $recipient_name
 * @property string $phone
 * @property int|null $area_id
 * @property string|null $area_other
 * @property string $address_line
 * @property bool $is_default
 */
#[Fillable(['customer_id', 'recipient_name', 'phone', 'area_id', 'area_other', 'address_line', 'is_default'])]
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'area_id' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
