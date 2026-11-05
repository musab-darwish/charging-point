{{-- Shared fields for adding and editing an item. $item is null when adding. --}}
@php $selected = old('category', $item?->category ?? $category ?? 'charging'); @endphp

<div class="space-y-6">
    <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_13rem]">
        <x-input name="name" label="اسم الصنف" :value="$item?->name" required autofocus autocomplete="off"
            hint="كما سيظهر على الزر، مثل: جوال مع شاحن." />

        <x-input name="price" label="السعر (₪)" :value="$item?->price" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="1.00"
            class="font-extrabold tabular-nums"
            hint="فارغ = يُحدَّد عند كل تسجيل." />
    </div>

    <fieldset>
        <legend class="mb-2 block text-sm font-bold">النوع</legend>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach (\App\Models\PriceItem::CATEGORIES as $value => $label)
                <label class="flex h-20 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-rule bg-white font-bold text-ink-muted transition hover:border-rule-strong has-checked:border-brand has-checked:bg-brand-soft has-checked:text-brand has-focus-visible:ring-4 has-focus-visible:ring-brand/20">
                    <input type="radio" name="category" value="{{ $value }}" @checked($selected === $value) class="sr-only">
                    <x-category-icon :category="$value" class="size-6" />
                    {{ $label }}
                </label>
            @endforeach
        </div>
        @error('category')
            <p class="mt-2 text-sm font-medium text-debit">{{ $message }}</p>
        @enderror
    </fieldset>
</div>
