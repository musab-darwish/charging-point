<?php

namespace App\Models;

use Database\Factories\PaymentAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An account customers transfer to: a bank account or a wallet app,
 * in someone's name, with its number.
 */
#[Fillable(['name', 'holder_name', 'number'])]
class PaymentAccount extends Model
{
    /** @use HasFactory<PaymentAccountFactory> */
    use HasFactory;

    /**
     * The table's defaults, so a new account knows it is active before it's
     * read back from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Accounts offered when recording a transfer (and, later, in the account message).
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('name')->orderBy('id');
    }

    /**
     * The transfers that arrived on this account.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Whether this account can be deleted: only one no transfer was recorded on.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->transactions()->exists();
    }

    /**
     * Stop offering the account for new transfers, or offer it again.
     */
    public function toggleActive(): void
    {
        $this->forceFill(['is_active' => ! $this->is_active])->save();
    }
}
