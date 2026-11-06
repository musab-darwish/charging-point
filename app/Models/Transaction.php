<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'customer_id', 'type', 'category', 'price_item_id', 'description', 'quantity', 'unit_price',
    'amount', 'occurred_at', 'method', 'payment_account_id', 'reference', 'freeze_item_id', 'reverses_id', 'form_token', 'user_id',
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
     * The freezer item a pickup charge is for.
     *
     * @return BelongsTo<FreezeItem, $this>
     */
    public function freezeItem(): BelongsTo
    {
        return $this->belongsTo(FreezeItem::class);
    }

    /**
     * The movement this one cancels, when it is a reversal.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function reverses(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reverses_id');
    }

    /**
     * The reversal that cancelled this movement, if it was cancelled.
     *
     * @return HasOne<Transaction, $this>
     */
    public function reversal(): HasOne
    {
        return $this->hasOne(Transaction::class, 'reverses_id');
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

    /**
     * Whether this movement cancels another one.
     */
    public function isReversal(): bool
    {
        return $this->reverses_id !== null;
    }

    /**
     * Whether a reversal cancelled this movement.
     */
    public function isReversed(): bool
    {
        return $this->reversal !== null;
    }

    /**
     * A movement can be cancelled once, and a cancellation itself never is.
     */
    public function canBeReversed(): bool
    {
        return ! $this->isReversal() && ! $this->isReversed();
    }

    public function isTransfer(): bool
    {
        return $this->type === self::CREDIT && $this->method === self::TRANSFER;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Mark a transfer as seen on the account ("تأكدت"). The only change a
     * movement ever takes: the amount and the balance stay as they are.
     * Marking it again keeps the first time.
     */
    public function verify(): void
    {
        if (! $this->isVerified()) {
            $this->forceFill(['verified_at' => now()])->save();
        }
    }

    /**
     * Undo a "تأكدت" pressed by mistake.
     */
    public function unverify(): void
    {
        $this->forceFill(['verified_at' => null])->save();
    }
}
