@extends('layouts.app')

@section('title', 'الأصناف والأسعار')

@section('content')
    <div class="stagger space-y-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold">الأصناف والأسعار</h1>
                <p class="mt-2 text-ink-muted">تظهر الأصناف بهذا الترتيب كأزرار في شغل اليوم. تغيير السعر لا يغيّر الحركات المسجّلة سابقاً.</p>
            </div>

            <a href="{{ route('prices.create') }}" class="inline-flex h-13 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97]">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                إضافة صنف
            </a>
        </header>

        <div class="gap-6 lg:columns-2 *:mb-6 *:break-inside-avoid">
            @foreach (\App\Models\PriceItem::CATEGORIES as $category => $label)
                @php $items = $groups->get($category, collect()); @endphp

                <section class="overflow-hidden rounded-2xl border border-rule bg-white">
                    <header class="flex items-center justify-between gap-3 border-b border-rule bg-sheet/60 px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="flex size-9 items-center justify-center rounded-xl bg-brand-soft text-brand">
                                <x-category-icon :category="$category" class="size-5" />
                            </span>
                            <h2 class="text-lg font-extrabold">{{ $label }}</h2>
                            <span class="rounded-md bg-paper px-2 py-0.5 text-xs font-bold tabular-nums text-ink-muted">{{ $items->count() }}</span>
                        </div>

                        <a href="{{ route('prices.create', ['category' => $category]) }}" class="inline-flex h-10 items-center gap-1 rounded-lg px-3 text-sm font-bold text-brand transition hover:bg-brand-soft">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            إضافة
                        </a>
                    </header>

                    @if ($items->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-ink-muted">لا توجد أصناف في {{ $label }} بعد.</p>
                    @else
                        <ul class="divide-y divide-rule">
                            @foreach ($items as $item)
                                <li @class(['flex items-center gap-3 py-2.5 ps-5 pe-3', 'bg-sheet/60' => ! $item->is_active])>
                                    <div class="min-w-0 flex-1">
                                        <p @class(['truncate font-bold', 'text-ink-muted line-through decoration-rule-strong' => ! $item->is_active])>{{ $item->name }}</p>
                                        @unless ($item->is_active)
                                            <p class="text-xs font-medium text-ink-muted">مخفي من شغل اليوم</p>
                                        @endunless
                                    </div>

                                    @if ($item->hasFixedPrice())
                                        <span class="shrink-0 text-lg font-extrabold tabular-nums">{{ $item->price }} <span class="text-sm text-ink-muted">₪</span></span>
                                    @else
                                        <span class="shrink-0 rounded-md border border-dashed border-rule-strong px-2 py-0.5 text-xs font-bold text-ink-muted">يُحدَّد عند التسجيل</span>
                                    @endif

                                    <div class="flex shrink-0 items-center">
                                        @if ($item->is_active)
                                            @foreach (['up' => 'M6 15l6-6 6 6', 'down' => 'M6 9l6 6 6-6'] as $direction => $path)
                                                <form method="POST" action="{{ route('prices.move', [$item, $direction]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" aria-label="{{ $direction === 'up' ? 'تحريك للأعلى' : 'تحريك للأسفل' }}: {{ $item->name }}"
                                                        @disabled($direction === 'up' ? $loop->parent->first : $items->where('is_active', true)->last()->is($item))
                                                        class="flex size-10 items-center justify-center rounded-lg text-ink-muted transition hover:bg-paper hover:text-ink disabled:pointer-events-none disabled:opacity-30">
                                                        <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $path }}" /></svg>
                                                    </button>
                                                </form>
                                            @endforeach
                                        @endif

                                        <a href="{{ route('prices.edit', $item) }}" aria-label="تعديل {{ $item->name }}" class="flex size-10 items-center justify-center rounded-lg text-ink-muted transition hover:bg-paper hover:text-ink">
                                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16z" /><path d="M13.5 6.5l4 4" /></svg>
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
@endsection
