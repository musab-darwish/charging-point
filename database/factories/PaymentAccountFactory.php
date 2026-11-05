<?php

namespace Database\Factories;

use App\Models\PaymentAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAccount>
 */
class PaymentAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['بنك فلسطين', 'جوال باي', 'بال باي']),
            'holder_name' => 'مصعب',
            'number' => fake()->unique()->numerify('059#######'),
        ];
    }

    /**
     * A hidden account, not offered for new transfers.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
