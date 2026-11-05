{{-- The day's totals, like adding up a page of the daily notebook --}}
<dl class="stagger grid grid-cols-2 gap-3 lg:grid-cols-4">
    <div class="rounded-2xl border border-rule bg-white p-4">
        <dt class="text-sm font-bold text-ink-muted">الزبائن</dt>
        <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ $sheet->rows->count() }}</dd>
    </div>
    <div class="rounded-2xl border border-rule bg-white p-4">
        <dt class="text-sm font-bold text-ink-muted">مجموع الشغل</dt>
        <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ $sheet->work }} <span class="text-base text-ink-muted">₪</span></dd>
    </div>
    <div class="rounded-2xl border border-rule bg-white p-4">
        <dt class="text-sm font-bold text-ink-muted">المدفوع</dt>
        <dd class="mt-1 text-3xl font-extrabold tabular-nums text-credit">{{ $sheet->paid }} <span class="text-base">₪</span></dd>
    </div>
    <div @class(['rounded-2xl border p-4', 'border-debit/25 bg-debit-soft/60' => bccomp($sheet->unpaid, '0', 2) > 0, 'border-rule bg-white' => bccomp($sheet->unpaid, '0', 2) === 0])>
        <dt class="text-sm font-bold text-ink-muted">لم يُدفع</dt>
        <dd class="mt-1 text-3xl font-extrabold tabular-nums text-debit">{{ $sheet->unpaid }} <span class="text-base">₪</span></dd>
    </div>
</dl>
