<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FreezeItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Something a customer left in the freezer. Nothing is owed while it is
 * there; the price is decided when it is picked up.
 */
#[Fillable(['customer_id', 'description', 'quantity', 'stored_at', 'user_id', 'form_token'])]
class FreezeItem extends Model
{
    /** @use HasFactory<FreezeItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stored_at' => 'datetime',
            'picked_up_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Who put it in the freezer.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Who handed it back.
     *
     * @return BelongsTo<User, $this>
     */
    public function pickedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_by');
    }

    /**
     * The charge made at pickup (none when it was free).
     *
     * @return HasOne<Transaction, $this>
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class)->where('type', Transaction::DEBIT);
    }

    /**
     * Still in the freezer, the longest-waiting first.
     *
     * @param  Builder<self>  $query
     */
    public function scopeInFreezer(Builder $query): void
    {
        $query->whereNull('picked_up_at')->orderBy('stored_at')->orderBy('id');
    }

    /**
     * Picked up in the last few days, the latest first.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRecentlyPickedUp(Builder $query, int $days = 7): void
    {
        $query->where('picked_up_at', '>=', CarbonImmutable::today()->subDays($days))
            ->orderByDesc('picked_up_at')
            ->orderByDesc('id');
    }

    public function isInFreezer(): bool
    {
        return $this->picked_up_at === null;
    }

    /**
     * Whole days in the freezer, counted by date like the notebook: put in
     * today is 0, yesterday is 1. Stops counting once it is picked up.
     */
    public function daysStored(): int
    {
        $until = CarbonImmutable::parse($this->picked_up_at ?? now())->startOfDay();

        return (int) CarbonImmutable::parse($this->stored_at)->startOfDay()->diffInDays($until);
    }

    /**
     * The item as one line, with its quantity: "كيكة وسط (2)".
     */
    public function label(): string
    {
        return $this->quantity > 1 ? "{$this->description} ({$this->quantity})" : $this->description;
    }

    /**
     * Only an item still in the freezer can be deleted (it was entered by
     * mistake and never cost anything).
     */
    public function canBeDeleted(): bool
    {
        return $this->isInFreezer();
    }
}
