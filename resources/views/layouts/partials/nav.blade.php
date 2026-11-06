{{-- Main navigation, shared by the laptop sidebar and the phone bottom bar --}}
<x-nav-item :layout="$layout" :href="route('day.today')" :active="request()->routeIs('day.*')">
    <x-slot:icon>
        <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h11l3 3v15H5z" /><path d="M9 8h6M9 12h6M9 16h4" /></svg>
    </x-slot:icon>
    شغل اليوم
</x-nav-item>

<x-nav-item :layout="$layout" :href="route('customers.index')" :active="request()->routeIs('customers.*')">
    <x-slot:icon>
        <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4" /><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6" /><path d="M16 4a4 4 0 0 1 0 8M22 20c0-3-2-5.2-5-5.8" /></svg>
    </x-slot:icon>
    الزبائن
</x-nav-item>

{{-- once(): the sidebar and the phone bar share one count --}}
<x-nav-item :layout="$layout" :href="route('freezer.index')" :active="request()->routeIs('freezer.*')"
    :count="once(fn () => \App\Models\FreezeItem::whereNull('picked_up_at')->count())">
    <x-slot:icon>
        <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20M4.9 6l14.2 12M4.9 18L19.1 6" /><path d="M9 3.5l3 2 3-2M9 20.5l3-2 3 2" /></svg>
    </x-slot:icon>
    التجميد
</x-nav-item>

<x-nav-item :layout="$layout" soon>
    <x-slot:icon>
        <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19h16" /><path d="M7 15V9M12 15V5M17 15v-3" /></svg>
    </x-slot:icon>
    المديونون
</x-nav-item>

<x-nav-item :layout="$layout" soon>
    <x-slot:icon>
        <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3l4 4-4 4" /><path d="M3 7h18" /><path d="M7 21l-4-4 4-4" /><path d="M21 17H3" /></svg>
    </x-slot:icon>
    الحوالات
</x-nav-item>

{{-- Owner tools: in the sidebar only; on phones they live in the user menu --}}
@if ($layout === 'side')
    @can('manage-users')
        <p class="mb-1 mt-6 px-3 text-xs font-bold text-ink-muted">الإدارة</p>

        <x-nav-item layout="side" :href="route('users.index')" :active="request()->routeIs('users.*')">
            <x-slot:icon>
                <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="8" r="4" /><path d="M3 20c0-3.5 3-6 7-6 1.4 0 2.7.3 3.8.9" /><path d="M18 14v6M15 17h6" /></svg>
            </x-slot:icon>
            المستخدمون
        </x-nav-item>
    @endcan

    @can('manage-prices')
        <x-nav-item layout="side" :href="route('prices.index')" :active="request()->routeIs('prices.*')">
            <x-slot:icon>
                <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12V4h8l10 10-8 8z" /><circle cx="7.5" cy="8.5" r="1.5" /></svg>
            </x-slot:icon>
            الأصناف والأسعار
        </x-nav-item>
    @endcan

    @can('manage-payment-accounts')
        <x-nav-item layout="side" :href="route('payment-accounts.index')" :active="request()->routeIs('payment-accounts.*')">
            <x-slot:icon>
                <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h4" /></svg>
            </x-slot:icon>
            حسابات التحويل
        </x-nav-item>
    @endcan
@endif
