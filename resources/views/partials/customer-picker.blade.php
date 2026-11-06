{{-- Pick a customer by name or phone, or add a new name (resources/js/behaviors/customer-picker.js).
     $pickerId names the fields; $walkIn offers the walk-in customer; $next is where focus goes after a pick.
     The form around it holds the [data-customer-id] and [data-new-customer] hidden fields. --}}
<div data-customer-picker data-search-url="{{ route('customers.search') }}"
    @isset($walkIn) data-walk-in='{{ json_encode(['id' => $walkIn->id, 'name' => $walkIn->name, 'balance' => '0.00', 'walkIn' => true]) }}' @endisset
    @isset($next) data-next="{{ $next }}" @endisset
    class="rounded-xl transition data-[missing=true]:ring-4 data-[missing=true]:ring-debit/25 data-[missing=true]:ring-offset-4 data-[missing=true]:ring-offset-white">
    <label for="{{ $pickerId }}-customer-search" class="sr-only">الزبون</label>

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
        <input id="{{ $pickerId }}-customer-search" type="search" autocomplete="off" placeholder="اسم الزبون أو رقم جواله"
            role="combobox" aria-expanded="false" aria-controls="{{ $pickerId }}-customer-results" aria-autocomplete="list"
            class="block h-14 w-full rounded-xl border border-rule-strong bg-white ps-12 pe-4 text-lg transition placeholder:text-ink-muted/60 hover:border-ink-muted focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
        <ul id="{{ $pickerId }}-customer-results" role="listbox" data-customer-results hidden
            class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 animate-drop overflow-y-auto rounded-xl border border-rule bg-white p-1.5 shadow-xl shadow-ink/10"></ul>
    </div>

</div>
