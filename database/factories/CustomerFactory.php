<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
            'phone' => '97059'.fake()->numerify('#######'),
            'notes' => null,
        ];
    }

    /**
     * A customer added for one visit.
     */
    public function oneTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_one_time' => true,
        ]);
    }

    /**
     * A customer without a phone number.
     */
    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
        ]);
    }
}
