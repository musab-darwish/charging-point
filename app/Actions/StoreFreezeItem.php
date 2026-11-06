<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\FreezeItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Put something in the freezer for a customer. Nothing is owed yet: the
 * price is set when it is picked up.
 */
class StoreFreezeItem
{
    /**
     * @return FreezeItem|null null when this form was already recorded
     */
    public function handle(Customer $customer, string $description, ?int $quantity, CarbonInterface $storedAt, User $user, string $formToken): ?FreezeItem
    {
        try {
            return $customer->freezeItems()->create([
                'description' => $description,
                'quantity' => $quantity,
                'stored_at' => $storedAt,
                'user_id' => $user->id,
                'form_token' => $formToken,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
