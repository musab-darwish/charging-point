@php
    use App\Support\DayStatus;

    $counts = $sheet->rows->countBy(fn ($row) => $row->status->state);
    $filters = [
        'all' => ['الكل', $sheet->rows->count()],
        DayStatus::UNPAID => ['لم يُدفع بعد', $counts[DayStatus::UNPAID] ?? 0],
        DayStatus::PARTIAL => ['متبقي', $counts[DayStatus::PARTIAL] ?? 0],
        DayStatus::PAID => ['واصل', $counts[DayStatus::PAID] ?? 0],
    ];
@endphp

{{-- Find a customer among the day's cards, by name or by state (resources/js/behaviors/day-filter.js) --}}
<div data-day-filter class="mb-4 flex flex-col gap-3 rounded-2xl border border-rule bg-white p-3 sm:flex-row sm:items-center">
    <div class="relative flex-1">
        <label for="day-filter-search" class="sr-only">البحث في زبائن اليوم</label>
        <span class="pointer-events-none absolute inset-y-0 start-3.5 flex items-center text-ink-muted">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M20 20l-4-4" /></svg>
        </span>
        <input id="day-filter-search" type="search" data-day-filter-search autocomplete="off" placeholder="ابحث في زبائن هذا اليوم"
            class="peer block h-12 w-full rounded-xl border border-rule-strong bg-sheet/60 ps-11 pe-12 transition placeholder:text-ink-muted/60 hover:border-ink-muted focus:border-brand focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand/15">
        <kbd class="pointer-events-none absolute inset-y-0 end-3 my-auto hidden h-6 items-center rounded border border-rule-strong bg-white px-1.5 font-sans text-xs font-bold text-ink-muted lg:flex lg:peer-focus:hidden lg:peer-[:not(:placeholder-shown)]:hidden">/</kbd>
    </div>

    <div role="group" aria-label="تصفية حسب الحالة" class="grid grid-cols-2 gap-1.5 sm:flex">
        @foreach ($filters as $value => [$label, $count])
            <button type="button" data-day-filter-status="{{ $value }}" aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}" @disabled($count === 0 && $value !== 'all')
                class="inline-flex h-11 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg px-3 text-sm font-bold text-ink-muted transition hover:bg-paper hover:text-ink disabled:pointer-events-none disabled:opacity-40 aria-pressed:bg-brand aria-pressed:text-white">
                {{ $label }}
                <span class="rounded-md bg-ink/5 px-1.5 text-xs tabular-nums in-aria-pressed:bg-white/20">{{ $count }}</span>
            </button>
        @endforeach
    </div>
</div>
