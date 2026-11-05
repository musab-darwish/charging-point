@php
    $oldCustomer = old('customer_id') ? \App\Models\Customer::find(old('customer_id')) : null;
    $oldState = [
        'customer' => $oldCustomer ? ['id' => $oldCustomer->id, 'name' => $oldCustomer->name, 'balance' => $oldCustomer->balance(), 'walkIn' => $oldCustomer->is_walk_in] : null,
        'newCustomer' => old('new_customer_name'),
        'items' => old('items', []),
    ];
@endphp

{{-- On a phone this panel is a full-screen sheet opened by "تسجيل شغل"; on a laptop it sits beside the list --}}
<aside id="work-entry" data-work-entry data-open="{{ $errors->hasAny(['customer_id', 'new_customer_name', 'items', 'items.*', 'date']) ? 'true' : 'false' }}"
    aria-label="تسجيل شغل"
    class="fixed inset-0 z-40 flex flex-col bg-sheet transition-transform duration-300 ease-out max-lg:invisible max-lg:translate-y-full max-lg:data-[open=true]:visible max-lg:data-[open=true]:translate-y-0 lg:sticky lg:top-6 lg:z-auto lg:max-h-[calc(100dvh-3rem)]">
    <form method="POST" action="{{ route('work.store') }}" data-submit-lock data-work-form data-old='{{ json_encode($oldState) }}'
        class="flex min-h-0 flex-1 flex-col overflow-hidden lg:rounded-2xl lg:border lg:border-rule lg:bg-white">
        @csrf
        <input type="hidden" name="date" value="{{ $sheet->day->format('Y-m-d') }}">
        <input type="hidden" name="form_token" value="{{ $workToken }}">
        <input type="hidden" name="customer_id" data-customer-id>
        <input type="hidden" name="new_customer_name" data-new-customer>

        <header class="flex items-center justify-between border-b border-rule px-5 py-4">
            <h2 class="text-lg font-extrabold">تسجيل شغل</h2>
            <button type="button" data-close-entry aria-label="إغلاق" class="flex size-10 items-center justify-center rounded-lg text-ink-muted transition hover:bg-paper lg:hidden">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </header>

        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5">
            {{-- 1. Customer --}}
            <div data-customer-picker data-search-url="{{ route('customers.search') }}"
                data-walk-in='{{ json_encode(['id' => $walkIn->id, 'name' => $walkIn->name, 'balance' => '0.00', 'walkIn' => true]) }}'>
                <label for="entry-customer-search" class="mb-1.5 block text-sm font-bold">الزبون</label>

                <div data-customer-chosen hidden class="flex items-center gap-3 rounded-xl border-2 border-brand bg-brand-soft/60 px-3 py-2.5">
                    <span data-chosen-initial class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand text-base font-bold text-white"></span>
                    <div class="min-w-0 flex-1">
                        <p data-chosen-name class="truncate font-bold"></p>
                        <p data-chosen-note class="text-xs font-medium"></p>
                    </div>
                    <button type="button" data-change-customer class="h-10 rounded-lg px-3 text-sm font-bold text-brand transition hover:bg-white">تغيير</button>
                </div>

                <div data-customer-search class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-3.5 flex items-center text-ink-muted">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M20 20l-4-4" /></svg>
                    </span>
                    <input id="entry-customer-search" type="search" autocomplete="off" placeholder="اسم الزبون أو رقم جواله"
                        role="combobox" aria-expanded="false" aria-controls="entry-customer-results" aria-autocomplete="list"
                        class="block h-13 w-full rounded-xl border border-rule-strong bg-white ps-11 pe-4 text-lg transition placeholder:text-ink-muted/60 hover:border-ink-muted focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
                    <ul id="entry-customer-results" role="listbox" data-customer-results hidden
                        class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 animate-drop overflow-y-auto rounded-xl border border-rule bg-white p-1.5 shadow-xl shadow-ink/10"></ul>
                </div>
            </div>

            {{-- 2. Items --}}
            <div>
                <p class="mb-2 text-sm font-bold">الأصناف</p>

                @if ($priceGroups->isEmpty())
                    <p class="rounded-xl border border-dashed border-rule-strong px-4 py-3 text-sm text-ink-muted">
                        لا توجد أصناف بعد.
                        @can('manage-prices')
                            <a href="{{ route('prices.create') }}" class="font-bold text-brand underline">أضف الأصناف وأسعارها</a> لتظهر هنا كأزرار.
                        @else
                            اطلب من المالك إضافتها.
                        @endcan
                    </p>
                @endif

                <div class="space-y-3">
                    @foreach (\App\Models\PriceItem::CATEGORIES as $category => $label)
                        @continue(! $priceGroups->has($category))
                        <div>
                            <p class="mb-1.5 flex items-center gap-1.5 text-xs font-bold text-ink-muted">
                                <x-category-icon :category="$category" class="size-3.5" />
                                {{ $label }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($priceGroups[$category] as $item)
                                    <button type="button" data-add-item='{{ json_encode(['id' => $item->id, 'name' => $item->name, 'price' => $item->price]) }}'
                                        class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-rule-strong bg-white px-3 py-1.5 text-start font-bold transition duration-150 hover:border-brand hover:bg-brand-soft/50 active:scale-[.96]">
                                        {{ $item->name }}
                                        <span class="rounded-md bg-paper px-1.5 py-0.5 text-xs font-bold tabular-nums text-ink-muted">{{ $item->price ?? 'سعر حر' }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <button type="button" data-add-free
                        class="inline-flex h-11 items-center gap-1.5 rounded-xl border border-dashed border-rule-strong px-3.5 text-sm font-bold text-ink-muted transition hover:border-ink-muted hover:text-ink">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                        بند آخر
                    </button>
                </div>
            </div>

            {{-- 3. What was added --}}
            <div>
                <p class="mb-2 text-sm font-bold">البنود</p>
                <p data-cart-empty class="rounded-xl border border-dashed border-rule px-4 py-5 text-center text-sm text-ink-muted">اضغط على صنف لإضافته هنا.</p>
                <ul data-cart class="space-y-2"></ul>
            </div>
        </div>

        <footer class="border-t border-rule bg-white px-5 py-4 max-lg:pb-[max(1rem,env(safe-area-inset-bottom))]">
            <div class="mb-3 flex items-baseline justify-between">
                <span class="font-bold text-ink-muted">المجموع</span>
                <span class="text-3xl font-extrabold tabular-nums"><span data-cart-total>0.00</span> <span class="text-lg text-ink-muted">₪</span></span>
            </div>

            <p data-walk-in-note hidden class="mb-3 rounded-lg bg-paper px-3 py-2 text-xs font-medium text-ink-muted">الزبون العابر يدفع فوراً، فيُسجَّل شغله واصلاً.</p>

            <div class="grid grid-cols-2 gap-2">
                <button type="submit" name="pay_now" value="0" data-save-on-account disabled
                    class="group inline-flex h-13 items-center justify-center gap-2 rounded-xl border-2 border-ink font-bold transition duration-150 hover:bg-paper active:scale-[.97] disabled:pointer-events-none disabled:opacity-40 aria-busy:opacity-80">
                    <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                    على الحساب
                </button>
                <button type="submit" name="pay_now" value="1" data-save-paid disabled
                    class="group inline-flex h-13 items-center justify-center gap-2 rounded-xl bg-brand font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97] disabled:pointer-events-none disabled:opacity-40 aria-busy:opacity-80">
                    <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                    <svg class="size-5 group-aria-busy:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                    تسجيل وواصل
                </button>
            </div>
        </footer>
    </form>

    {{-- One line in the list, filled in by resources/js/behaviors/work-entry.js --}}
    <template data-line-template>
        <li data-line class="animate-rise rounded-xl border border-rule bg-sheet/70 p-3">
            <input type="hidden" data-field="price_item_id">
            <div class="flex items-center gap-2">
                <p data-line-name class="min-w-0 flex-1 truncate font-bold"></p>
                <input data-field="description" type="text" maxlength="150" placeholder="وصف البند، مثل: تصليح شاحن"
                    class="h-10 min-w-0 flex-1 rounded-lg border border-rule-strong bg-white px-3 font-bold focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
                <button type="button" data-remove-line aria-label="حذف البند" class="flex size-9 shrink-0 items-center justify-center rounded-lg text-ink-muted transition hover:bg-debit-soft hover:text-debit">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <div class="flex items-center rounded-lg border border-rule-strong bg-white" dir="ltr">
                    <button type="button" data-qty-down aria-label="إنقاص الكمية" class="flex size-10 items-center justify-center text-lg font-bold transition hover:bg-paper">−</button>
                    <input data-field="quantity" type="text" inputmode="numeric" value="1" aria-label="الكمية"
                        class="h-10 w-10 border-x border-rule text-center font-extrabold tabular-nums focus:outline-none">
                    <button type="button" data-qty-up aria-label="زيادة الكمية" class="flex size-10 items-center justify-center text-lg font-bold transition hover:bg-paper">+</button>
                </div>
                <span class="text-ink-muted">×</span>
                <div class="relative w-24" dir="ltr">
                    <input data-field="unit_price" type="text" inputmode="decimal" placeholder="السعر" aria-label="سعر الوحدة"
                        class="h-10 w-full rounded-lg border border-rule-strong bg-white ps-3 pe-7 text-end font-bold tabular-nums focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
                    <span class="pointer-events-none absolute inset-y-0 end-2.5 flex items-center text-sm text-ink-muted">₪</span>
                </div>
                <span class="ms-auto text-lg font-extrabold tabular-nums" data-line-total>0.00</span>
            </div>
        </li>
    </template>
</aside>
