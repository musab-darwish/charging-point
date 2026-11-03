{{-- What a regular user can and cannot do, shown beside the user forms --}}
<aside class="ledger-paper rounded-2xl border border-rule py-6 ps-14 pe-6">
    <h2 class="font-extrabold">صلاحيات المستخدم</h2>

    <ul class="mt-4 space-y-3 text-sm">
        @foreach (['تسجيل شغل اليوم والدفعات', 'إضافة الزبائن وتعديلهم', 'عرض كشوف الحساب'] as $allowed)
            <li class="flex items-center gap-2.5">
                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand text-white">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                </span>
                {{ $allowed }}
            </li>
        @endforeach

    </ul>

    <h3 class="mt-5 text-xs font-bold text-ink-muted">للمالك فقط</h3>
    <ul class="mt-2.5 space-y-3 text-sm">
        @foreach (['إدارة المستخدمين', 'تعديل قائمة الأسعار'] as $denied)
            <li class="flex items-center gap-2.5 text-ink-muted">
                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-rule text-ink-muted">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M7 7l10 10M17 7L7 17" /></svg>
                </span>
                {{ $denied }}
            </li>
        @endforeach
    </ul>

    <p class="mt-5 border-t border-rule-strong/60 pt-4 text-sm leading-relaxed text-ink-muted">
        كل حركة تُسجَّل باسم من أدخلها. لا يُحذف المستخدم، بل يُوقَف، فتبقى حركاته باسمه.
    </p>
</aside>
