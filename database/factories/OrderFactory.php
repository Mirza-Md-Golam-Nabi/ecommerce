<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Bare order without items. Prefer App\Actions\Order\PlaceOrder in tests that need real totals.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.now()->format('ymd').'-'.fake()->unique()->numerify('#####'),
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'delivery_charge' => 6000,
            'shipping_name' => 'রহিম উদ্দিন',
            'shipping_phone' => fake()->numerify('017########'),
            'area_id' => null,
            'area_name' => 'ধানমন্ডি',
            'address_line' => 'বাড়ি ১২, রোড ৫',
            'placed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (): array => array_filter([
            'status' => $status,
            $status->timestampColumn() => now(),
        ]));
    }
}
