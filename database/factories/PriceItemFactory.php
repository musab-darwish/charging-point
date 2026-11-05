<?php

namespace Database\Factories;

use App\Models\PriceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceItem>
 */
class PriceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'category' => 'charging',
            'price' => fake()->randomElement(['1.00', '2.00', '3.00', '5.00']),
        ];
    }

    /**
     * An item whose price is entered each time.
     */
    public function variablePrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => null,
        ]);
    }
}
