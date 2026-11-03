@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <h1 class="text-2xl font-bold">مرحباً بك في نقطة شحن</h1>
    <p class="mt-1 text-ink-muted">صفحة تجريبية لعرض الهوية البصرية.</p>

    <div class="mt-6">
        <span class="text-sm font-medium text-ink-muted">الرصيد · عليه</span>
        <div class="flex items-baseline gap-1.5 text-debit">
            <span class="text-5xl font-extrabold tabular-nums">47.50</span>
            <span class="text-2xl font-bold">₪</span>
        </div>
        <div class="mt-1 h-[7px] w-36 border-y-2 border-debit"></div>
    </div>

    <div class="mt-6 flex gap-3">
        <button type="button" class="h-13 flex-1 rounded-lg bg-ink text-lg font-bold text-sheet">+ عليه</button>
        <button type="button" class="h-13 flex-1 rounded-lg border-2 border-credit text-lg font-bold text-credit">دفعة</button>
    </div>

    <div class="mt-8 border-b border-rule">
        <div class="flex h-16 items-center justify-between border-t border-rule">
            <div>
                <p class="font-medium">شحن جوال مع شاحن</p>
                <p class="text-xs text-ink-muted">اليوم · 14:20</p>
            </div>
            <div class="text-end text-debit">
                <p class="font-bold tabular-nums">1.00</p>
                <p class="text-xs font-medium">عليه</p>
            </div>
        </div>
        <div class="flex h-16 items-center justify-between border-t border-rule">
            <div>
                <p class="font-medium">دفعة نقدية</p>
                <p class="text-xs text-ink-muted">أمس · 19:40</p>
            </div>
            <div class="text-end text-credit">
                <p class="font-bold tabular-nums">20.00</p>
                <p class="text-xs font-medium">مدفوع</p>
            </div>
        </div>
    </div>
@endsection
