<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'notes'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /** Country prefixes a phone number can have. 970 is the default. */
    public const PHONE_PREFIXES = ['970', '972'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_walk_in' => 'boolean',
        ];
    }

    /**
     * The shared walk-in customer.
     */
    public static function walkIn(): self
    {
        return static::where('is_walk_in', true)->firstOrFail();
    }

    /**
     * Customers with a name, without the walk-in.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRegular(Builder $query): void
    {
        $query->where('is_walk_in', false);
    }

    /**
     * Search by name, phone or notes. A phone typed the local way
     * (0599...) matches the stored international form (970599...).
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $digits = ltrim(preg_replace('/\D/', '', $term), '0');

        $query->where(function (Builder $query) use ($term, $digits) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%");

            if (strlen($digits) >= 3) {
                $query->orWhere('phone', 'like', "%{$digits}%");
            }
        });
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * What the customer owes: all debits minus all credits, worked out from
     * the transactions every time (never stored). Positive means they owe,
     * negative means they paid ahead, "0.00" means settled.
     */
    public function balance(): string
    {
        $balance = $this->transactions()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END), 0) AS balance")
            ->value('balance');

        return number_format((float) $balance, 2, '.', '');
    }

    /**
     * The balance moved over from the debt notebook, unless it was cancelled.
     */
    public function openingBalance(): ?Transaction
    {
        return $this->transactions()
            ->where('category', Transaction::OPENING)
            ->whereNull('reverses_id')
            ->whereDoesntHave('reversal')
            ->first();
    }

    /**
     * What they left in the freezer, now or before.
     *
     * @return HasMany<FreezeItem, $this>
     */
    public function freezeItems(): HasMany
    {
        return $this->hasMany(FreezeItem::class);
    }

    /**
     * Whether this customer can be deleted: only one with no records and
     * nothing ever left in the freezer.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->is_walk_in
            && ! $this->transactions()->exists()
            && ! $this->freezeItems()->exists();
    }

    /**
     * The phone's country prefix (970 or 972).
     */
    public function phonePrefix(): Attribute
    {
        return Attribute::get(fn () => $this->phone ? substr($this->phone, 0, 3) : self::PHONE_PREFIXES[0]);
    }

    /**
     * The phone as people type it: 0599123456.
     */
    public function phoneLocal(): Attribute
    {
        return Attribute::get(fn () => $this->phone ? '0'.substr($this->phone, 3) : null);
    }

    /**
     * The phone grouped for reading: 0599 123 456.
     */
    public function phoneDisplay(): Attribute
    {
        return Attribute::get(function () {
            $local = $this->phone_local;

            return $local ? substr($local, 0, 4).' '.substr($local, 4, 3).' '.substr($local, 7) : null;
        });
    }
}
