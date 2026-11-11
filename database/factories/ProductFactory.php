<?php

namespace Database\Factories;

use App\Enums\SaleType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(20, 600) * 100;

        return [
            'category_id' => Category::factory(),
            'name' => fake()->randomElement(['রাঁধুনি হলুদ গুঁড়া', 'মিনিকেট চাল', 'মসুর ডাল', 'সয়াবিন তেল', 'চিনি', 'লবণ', 'চা পাতা', 'বিস্কুট']),
            'slug' => fake()->unique()->slug(3),
            'search_keywords' => null,
            'description' => null,
            'image' => null,
            'sale_type' => SaleType::Fixed,
            'unit_label' => fake()->randomElement(['২০০ গ্রাম', '৫০০ গ্রাম', '১ কেজি', '১ লিটার', '১২ পিস']),
            'nominal_weight_grams' => null,
            'price' => $price,
            'compare_at_price' => null,
            'estimated_cost' => (int) round($price * 0.85),
            'max_per_order' => null,
            'is_available' => true,
            'is_active' => true,
        ];
    }

    /**
     * Sold by weight: price is per nominal unit (default 1 kg).
     */
    public function weighted(int $nominalGrams = 1000): static
    {
        return $this->state(fn (): array => [
            'sale_type' => SaleType::Weighted,
            'nominal_weight_grams' => $nominalGrams,
            'unit_label' => '১ কেজি (± ৫০ গ্রাম)',
        ]);
    }

    public function discounted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'compare_at_price' => (int) round($attributes['price'] * 1.15),
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => ['is_available' => false]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
