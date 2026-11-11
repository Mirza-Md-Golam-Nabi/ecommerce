<?php

namespace App\Actions\Customer;

use App\Models\Customer;
use App\Models\CustomerAddress;

/**
 * Create or update the customer's default delivery address (prefilled at the next checkout).
 */
final class SaveDefaultAddress
{
    /**
     * @param  array{recipient_name: string, phone: string, area_id: int|null, area_other: string|null, address_line: string}  $address
     */
    public function handle(Customer $customer, array $address): CustomerAddress
    {
        return CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'is_default' => true],
            [
                'recipient_name' => $address['recipient_name'],
                'phone' => $address['phone'],
                'area_id' => $address['area_id'],
                'area_other' => $address['area_id'] === null ? $address['area_other'] : null,
                'address_line' => $address['address_line'],
            ],
        );
    }
}
