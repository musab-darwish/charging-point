@php
    $oldCustomer = old('customer_id') ? \App\Models\Customer::find(old('customer_id')) : null;
    $oldState = [
        'customer' => $oldCustomer ? ['id' => $oldCustomer->id, 'name' => $oldCustomer->name, 'balance' => $oldCustomer->balance(), 'walkIn' => $oldCustomer->is_walk_in] : null,
        'newCustomer' => old('new_customer_name'),
        'items' => old('items', []),
    ];

    // Number the first nine items (across all kinds) for the 1-9 keys.
    $shortcut = 0;
@endphp

{{-- A register in a dialog: full screen on a phone, a large window on a laptop.
     Customer and items on one side, the receipt on the other. --}}
<dialog id="work-entry" data-work-entry aria-label="تسجيل شغل"
    data-open-on-load="{{ $errors->hasAny(['customer_id', 'new_customer_name', 'items', 'items.*', 'date']) ? 'true' : 'false' }}"
    class="m-0 h-dvh max-h-none w-full max-w-none overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45 lg:m-auto lg:h-[min(100dvh-3rem,54rem)] lg:w-[min(100%-3rem,78rem)]">
    <form method="POST" action="{{ route('work.store') }}" data-submit-lock data-work-form data-old='{{ json_encode($oldState) }}'
        class="flex h-full flex-col overflow-hidden bg-sheet max-lg:animate-sheet-up lg:animate-drop lg:rounded-3xl lg:border lg:border-rule lg:bg-white lg:shadow-2xl lg:shadow-ink/25">
        @csrf
        <input type="hidden" name="date" value="{{ $day->format('Y-m-d') }}">
        <input type="hidden" name="form_token" value="{{ $workToken }}">
        @isset($backToCustomer)
            <input type="hidden" name="back_to_customer" value="{{ $backToCustomer->id }}">
        @endisset
        <input type="hidden" name="customer_id" data-customer-id>
        <input type="hidden" name="new_customer_name" data-new-customer>

        <header class="flex items-center gap-4 border-b border-rule px-5 py-3.5">
            <h2 class="text-lg font-extrabold">تسجيل شغل</h2>
            <p class="me-auto hidden items-center gap-1.5 text-xs text-ink-muted lg:flex">
                اكتب الاسم <kbd class="rounded border border-rule-strong bg-sheet px-1.5 font-sans font-bold">Enter</kbd>
                ثم الأصناف بالأرقام <kbd class="rounded border border-rule-strong bg-sheet px-1.5 font-sans font-bold tabular-nums">1–9</kbd>
            </p>
            <button type="button" data-close-entry aria-label="إغلاق" class="ms-auto flex size-10 items-center justify-center rounded-lg text-ink-muted transition hover:bg-paper">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </header>

        <div class="min-h-0 flex-1 max-lg:overflow-y-auto max-lg:pb-52 lg:grid lg:grid-cols-[minmax(0,1fr)_23rem] xl:grid-cols-[minmax(0,1fr)_26rem]">
            {{-- Customer and items --}}
            <div class="space-y-5 px-5 py-5 lg:overflow-y-auto">
                <div data-customer-picker data-search-url="{{ route('customers.search') }}"
                    data-walk-in='{{ json_encode(['id' => $walkIn->id, 'name' => $walkIn->name, 'balance' => '0.00', 'walkIn' => true]) }}'
                    class="rounded-xl transition data-[missing=true]:ring-4 data-[missing=true]:ring-debit/25 data-[missing=true]:ring-offset-4 data-[missing=true]:ring-offset-white">
                    <label for="entry-customer-search" class="sr-only">الزبون</label>

                    <div data-customer-chosen hidden class="flex items-center gap-3 rounded-xl border-2 border-brand bg-brand-soft/60 px-3 py-2">
                        <span data-chosen-initial class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand text-base font-bold text-white"></span>
                        <div class="min-w-0 flex-1">
                            <p data-chosen-name class="truncate text-lg font-bold"></p>
                            <p data-chosen-note class="text-xs font-medium"></p>
                        </div>
                        <button type="button" data-change-customer class="h-10 rounded-lg px-3 text-sm font-bold text-brand transition hover:bg-white">تغيير</button>
                    </div>

                    <div data-customer-search class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-ink-muted">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M20 20l-4-4" /></svg>
                        </span>
                        <input id="entry-customer-search" type="search" autocomplete="off" placeholder="اسم الزبون أو رقم جواله"
                            role="combobox" aria-expanded="false" aria-controls="entry-customer-results" aria-autocomplete="list"
                            class="block h-14 w-full rounded-xl border border-rule-strong bg-white ps-12 pe-4 text-lg transition placeholder:text-ink-muted/60 hover:border-ink-muted focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
                        <ul id="entry-customer-results" role="listbox" data-customer-results hidden
                            class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 animate-drop overflow-y-auto rounded-xl border border-rule bg-white p-1.5 shadow-xl shadow-ink/10"></ul>
                    </div>

                </div>

                @if ($priceGroups->isEmpty())
                    <p class="rounded-xl border border-dashed border-rule-strong px-4 py-3 text-sm text-ink-muted">
                        لا توجد أصناف بعد.
                        @can('manage-prices')
                            <a href="{{ route('prices.create') }}" class="font-bold text-brand underline">أضف الأصناف وأسعارها</a> لتظهر هنا.
                        @else
                            اطلب من المالك إضافتها.
                        @endcan
                    </p>
                @endif

                @foreach (\App\Models\PriceItem::CATEGORIES as $category => $label)
                    @continue(! $priceGroups->has($category))
                    <section aria-label="{{ $label }}">
                        <h3 class="mb-2 flex items-center gap-1.5 text-sm font-bold text-ink-muted">
                            <x-category-icon :category="$category" class="size-4" />
                            {{ $label }}
                        </h3>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 2xl:grid-cols-4">
                            @foreach ($priceGroups[$category] as $item)
                                @php $shortcut++; @endphp
                                <button type="button" data-add-item='{{ json_encode(['id' => $item->id, 'name' => $item->name, 'price' => $item->price]) }}'
                                    @if ($shortcut <= 9) data-shortcut="{{ $shortcut }}" @endif
                                    class="relative flex min-h-18 flex-col justify-between gap-1 rounded-xl border border-rule-strong bg-white p-3 text-start transition duration-150 hover:border-brand hover:bg-brand-soft/40 focus-visible:border-brand active:scale-[.96]">
                                    @if ($shortcut <= 9)
                                        <kbd class="absolute end-2 top-2 hidden min-w-5 rounded border border-rule bg-sheet px-1 text-center font-sans text-[11px] font-bold tabular-nums text-ink-muted lg:block">{{ $shortcut }}</kbd>
                                    @endif
                                    <span class="font-bold leading-snug lg:pe-6">{{ $item->name }}</span>
                                    <span class="text-sm font-extrabold tabular-nums text-brand">{{ $item->price ? $item->price.' ₪' : 'سعر حر' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <button type="button" data-add-free
                    class="inline-flex h-12 items-center gap-2 rounded-xl border-2 border-dashed border-rule-strong px-4 font-bold text-ink-muted transition hover:border-ink-muted hover:text-ink active:scale-[.97]">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                    بند آخر (شغلة ليست في القائمة)
                </button>
            </div>

            {{-- The receipt --}}
            <div class="flex flex-col bg-sheet/50 lg:min-h-0 lg:border-s lg:border-rule">
                <div class="flex-1 px-5 py-5 lg:overflow-y-auto">
                    <p class="mb-2 text-sm font-bold">البنود</p>
                    <p data-cart-empty class="rounded-xl border border-dashed border-rule px-4 py-6 text-center text-sm text-ink-muted">اضغط على صنف لإضافته هنا.</p>
                    <ul data-cart class="space-y-2"></ul>
                </div>

                <footer class="border-t border-rule bg-white px-5 py-4 max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-10 max-lg:pb-[max(1rem,env(safe-area-inset-bottom))] max-lg:shadow-[0_-8px_24px_-12px_rgb(23_37_31/0.25)]">
                    <div class="mb-3 flex items-baseline justify-between">
                        <span class="font-bold text-ink-muted">المجموع</span>
                        <span class="text-3xl font-extrabold tabular-nums"><span data-cart-total>0.00</span> <span class="text-lg text-ink-muted">₪</span></span>
                    </div>

                    <p data-walk-in-note hidden class="mb-3 rounded-lg bg-paper px-3 py-2 text-xs font-medium text-ink-muted">الزبون العابر يدفع فوراً، فيُسجَّل شغله واصلاً.</p>

                    <p data-entry-error role="alert" hidden class="mb-3 flex animate-rise items-center gap-2 rounded-lg bg-debit-soft px-3 py-2 text-sm font-bold text-debit">
                        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg>
                        <span data-entry-error-text></span>
                    </p>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="submit" name="pay_now" value="0" data-save-on-account
                            class="group inline-flex h-13 items-center justify-center gap-2 rounded-xl border-2 border-ink font-bold transition duration-150 hover:bg-paper active:scale-[.97] disabled:pointer-events-none disabled:opacity-40 aria-busy:opacity-80">
                            <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                            على الحساب
                        </button>
                        <button type="submit" name="pay_now" value="1" data-save-paid
                            class="group inline-flex h-13 items-center justify-center gap-2 rounded-xl bg-brand font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97] disabled:pointer-events-none disabled:opacity-40 aria-busy:opacity-80">
                            <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                            <svg class="size-5 group-aria-busy:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                            تسجيل وواصل
                        </button>
                    </div>
                </footer>
            </div>
        </div>
    </form>

    {{-- One line on the receipt, filled in by resources/js/behaviors/work-entry.js --}}
    <template data-line-template>
        <li data-line class="animate-rise rounded-xl border border-rule bg-white p-2.5">
            <input type="hidden" data-field="price_item_id">
            <div class="flex items-center gap-2">
                <p data-line-name class="min-w-0 flex-1 truncate font-bold"></p>
                <input data-field="description" type="text" maxlength="150" placeholder="وصف البند، مثل: طحن سكر"
                    class="h-10 min-w-0 flex-1 rounded-lg border border-rule-strong bg-white px-3 font-bold focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15 aria-[invalid=true]:border-debit aria-[invalid=true]:ring-4 aria-[invalid=true]:ring-debit/15">
                <button type="button" data-remove-line aria-label="حذف البند" class="flex size-9 shrink-0 items-center justify-center rounded-lg text-ink-muted transition hover:bg-debit-soft hover:text-debit">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <div class="flex items-center rounded-lg border border-rule-strong bg-white" dir="ltr">
                    <button type="button" data-qty-down aria-label="إنقاص الكمية" class="flex size-9 items-center justify-center text-lg font-bold transition hover:bg-paper">−</button>
                    <input data-field="quantity" type="text" inputmode="numeric" value="1" aria-label="الكمية"
                        class="h-9 w-9 border-x border-rule text-center font-extrabold tabular-nums focus:outline-none">
                    <button type="button" data-qty-up aria-label="زيادة الكمية" class="flex size-9 items-center justify-center text-lg font-bold transition hover:bg-paper">+</button>
                </div>
                <span class="text-ink-muted">×</span>
                <div class="relative w-22" dir="ltr">
                    <input data-field="unit_price" type="text" inputmode="decimal" placeholder="السعر" aria-label="سعر الوحدة"
                        class="h-9 w-full rounded-lg border border-rule-strong bg-white ps-2.5 pe-6 text-end font-bold tabular-nums focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15 aria-[invalid=true]:border-debit aria-[invalid=true]:ring-4 aria-[invalid=true]:ring-debit/15">
                    <span class="pointer-events-none absolute inset-y-0 end-2 flex items-center text-sm text-ink-muted">₪</span>
                </div>
                <span class="ms-auto text-lg font-extrabold tabular-nums" data-line-total>0.00</span>
            </div>
        </li>
    </template>
</dialog>
