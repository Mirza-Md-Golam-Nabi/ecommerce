<?php

namespace Database\Factories;

use App\Enums\OrderItemStatus;
use App\Enums\SaleType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'category_id' => fn (array $attributes): int => (int) Product::query()->whereKey($attributes['product_id'])->value('category_id'),
            'product_name' => 'মসুর ডাল',
            'unit_label' => '১ কেজি',
            'sale_type' => SaleType::Fixed,
            'nominal_weight_grams' => null,
            'unit_price' => 14000,
            'quantity' => 1,
            'line_total' => fn (array $attributes): int => $attributes['unit_price'] * $attributes['quantity'],
            'estimated_unit_cost' => 12000,
            'line_cost' => fn (array $attributes): int => $attributes['estimated_unit_cost'] * $attributes['quantity'],
            'status' => OrderItemStatus::Pending,
        ];
    }

    public function weighted(int $nominalGrams = 1000): static
    {
        return $this->state(fn (): array => [
            'sale_type' => SaleType::Weighted,
            'nominal_weight_grams' => $nominalGrams,
            'unit_label' => '১ কেজি (± ৫০ গ্রাম)',
        ]);
    }
}
