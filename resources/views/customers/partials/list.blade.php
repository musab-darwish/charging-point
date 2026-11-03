{{-- The customer list, also returned alone to the live search --}}
@if ($customers->isEmpty())
    <div class="rounded-2xl border border-dashed border-rule-strong bg-white/60 px-6 py-12 text-center">
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-paper text-ink-muted">
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4" /><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6" /><path d="M16 4a4 4 0 0 1 0 8M22 20c0-3-2-5.2-5-5.8" /></svg>
        </span>

        @if ($search !== '')
            <p class="mt-4 text-lg font-bold">لا يوجد زبون يطابق «{{ $search }}»</p>
            <a href="{{ route('customers.create', ['name' => $search]) }}" class="mt-5 inline-flex h-12 items-center gap-2 rounded-xl border-2 border-brand px-5 font-bold text-brand transition hover:bg-brand-soft">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                إضافة «{{ $search }}» كزبون جديد
            </a>
        @else
            <p class="mt-4 text-lg font-bold">لا يوجد زبائن بعد</p>
            <p class="mt-1 text-ink-muted">أضف أول زبون لتبدأ تسجيل حساباته.</p>
        @endif
    </div>
@else
    <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($customers as $customer)
            <li>
                <a href="{{ route('customers.edit', $customer) }}" class="group flex h-full items-center gap-4 rounded-2xl border border-rule bg-white p-4 transition duration-150 hover:-translate-y-0.5 hover:border-rule-strong hover:shadow-lg hover:shadow-ink/5">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-paper text-lg font-bold text-brand transition group-hover:bg-brand group-hover:text-white">
                        {{ mb_substr($customer->name, 0, 1) }}
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-lg font-bold">{{ $customer->name }}</span>
                        <span class="mt-0.5 block truncate text-sm text-ink-muted">
                            @if ($customer->phone)
                                <span dir="ltr" class="tabular-nums">{{ $customer->phone_display }}</span>
                            @else
                                بدون رقم
                            @endif
                            @if ($customer->notes)
                                <span class="mx-1 text-rule-strong">·</span>{{ $customer->notes }}
                            @endif
                        </span>
                    </span>

                    <svg class="size-5 shrink-0 text-rule-strong transition group-hover:-translate-x-1 group-hover:text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6" /></svg>
                </a>
            </li>
        @endforeach
    </ul>
@endif
