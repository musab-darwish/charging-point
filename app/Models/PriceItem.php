<?php

namespace App\Models;

use Database\Factories\PriceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'category', 'price'])]
class PriceItem extends Model
{
    /** @use HasFactory<PriceItemFactory> */
    use HasFactory;

    /** The kinds of item, with their Arabic names, in display order. */
    public const CATEGORIES = [
        'charging' => 'الشحن',
        'freezing' => 'التجميد',
        'drink' => 'المشروبات',
        'other' => 'أخرى',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * New items go to the end of the list.
     */
    protected static function booted(): void
    {
        static::creating(function (PriceItem $item) {
            $item->sort_order = (int) static::max('sort_order') + 1;
        });
    }

    /**
     * Items shown in today's work.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * In the owner's chosen order.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Whether the price is asked for each time it's recorded.
     */
    public function hasFixedPrice(): bool
    {
        return $this->price !== null;
    }

    /**
     * Whether this item can be deleted: only one never used in a record.
     * Update this when transactions exist.
     */
    public function canBeDeleted(): bool
    {
        return true;
    }

    /**
     * Hide the item from today's work, or show it again.
     */
    public function toggleActive(): void
    {
        $this->forceFill(['is_active' => ! $this->is_active])->save();
    }

    /**
     * Swap places with the neighbouring item of the same kind.
     *
     * @param  'up'|'down'  $direction
     */
    public function move(string $direction): void
    {
        $neighbour = static::where('category', $this->category)
            ->where('sort_order', $direction === 'up' ? '<' : '>', $this->sort_order)
            ->orderBy('sort_order', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if (! $neighbour) {
            return;
        }

        [$this->sort_order, $neighbour->sort_order] = [$neighbour->sort_order, $this->sort_order];

        $this->save();
        $neighbour->save();
    }
}
