{{-- Main navigation, shared by the laptop sidebar and the phone bottom bar --}}
<x-nav-item :layout="$layout" :href="route('home')" :active="request()->routeIs('home')">
    <x-slot:icon>
        <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-7 9 7" /><path d="M5 10v10h14V10" /><path d="M10 20v-6h4v6" /></svg>
    </x-slot:icon>
    الرئيسية
</x-nav-item>

<x-nav-item :layout="$layout" soon>
    <x-slot:icon>
        <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4" /><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6" /><path d="M16 4a4 4 0 0 1 0 8M22 20c0-3-2-5.2-5-5.8" /></svg>
    </x-slot:icon>
    الزبائن
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
@endif
