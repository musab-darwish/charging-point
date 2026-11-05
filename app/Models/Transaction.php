<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id', 'type', 'category', 'price_item_id', 'description', 'quantity', 'unit_price',
    'amount', 'occurred_at', 'method', 'payment_account_id', 'reference', 'form_token', 'user_id',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    public const DEBIT = 'debit';

    public const CREDIT = 'credit';

    public const CASH = 'cash';

    public const TRANSFER = 'transfer';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'occurred_at' => 'datetime',
            'verified_at' => 'datetime',
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
     * @return BelongsTo<PriceItem, $this>
     */
    public function priceItem(): BelongsTo
    {
        return $this->belongsTo(PriceItem::class);
    }

    /**
     * The account a transfer arrived on.
     *
     * @return BelongsTo<PaymentAccount, $this>
     */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    /**
     * Who recorded it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeDebits(Builder $query): void
    {
        $query->where('type', self::DEBIT);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCredits(Builder $query): void
    {
        $query->where('type', self::CREDIT);
    }

    /**
     * Movements that happened on a given day (in the app's time zone).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOnDay(Builder $query, CarbonInterface $day): void
    {
        $query->whereBetween('occurred_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);
    }

    public function isDebit(): bool
    {
        return $this->type === self::DEBIT;
    }
}
