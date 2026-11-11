<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'recipient_name' => 'রহিম উদ্দিন',
            'phone' => fake()->numerify('018########'),
            'area_id' => Area::factory(),
            'area_other' => null,
            'address_line' => 'বাড়ি ১২, রোড ৫',
            'is_default' => true,
        ];
    }

    /**
     * "অন্যান্য" area typed by the customer.
     */
    public function otherArea(string $name = 'কামরাঙ্গীরচর'): static
    {
        return $this->state(fn (): array => ['area_id' => null, 'area_other' => $name]);
    }
}
