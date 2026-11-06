<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\FreezeItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreezeItem>
 */
class FreezeItemFactory extends Factory
{
    /**
     * An item in the freezer since now.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'description' => fake()->randomElement(['كيكة وسط', 'قنينة مي', 'كريمة']),
            'quantity' => null,
            'stored_at' => now(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Already handed back.
     */
    public function pickedUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'picked_up_at' => now(),
        ]);
    }
}
