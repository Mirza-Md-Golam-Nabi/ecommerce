<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => fake()->randomElement(['খাবার', 'রান্না', 'মসলা', 'চাল', 'ডাল', 'তেল', 'পানীয়', 'নাস্তা', 'পরিষ্কার', 'শিশুর যত্ন']),
            'slug' => fake()->unique()->slug(2),
            'icon' => null,
            'is_active' => true,
            'show_on_home' => false,
            'home_sort_order' => 0,
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn (): array => ['parent_id' => $parent->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function onHome(int $order = 0): static
    {
        return $this->state(fn (): array => ['show_on_home' => true, 'home_sort_order' => $order]);
    }
}
