@extends('layouts.app')

@section('title', 'حسابات التحويل')

@section('content')
    <div class="stagger space-y-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold">حسابات التحويل</h1>
                <p class="mt-2 max-w-2xl text-ink-muted">الحسابات التي يحوّل عليها الزبائن. تظهر الحسابات المتاحة عند تسجيل حوالة، وفي رسالة الحساب التي تُرسل للزبون.</p>
            </div>

            <a href="{{ route('payment-accounts.create') }}" class="inline-flex h-13 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97]">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                إضافة حساب
            </a>
        </header>

        @if ($accounts->isEmpty())
            <div class="ledger-paper rounded-2xl border border-rule py-14 ps-14 pe-6 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-white text-ink-muted">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h4" /></svg>
                </span>
                <p class="mt-4 text-lg font-bold">لا توجد حسابات تحويل بعد</p>
                <p class="mt-1 text-ink-muted">أضف الحساب البنكي أو المحفظة التي يحوّل عليها الزبائن، لتسجيل الحوالات عليها.</p>
                <a href="{{ route('payment-accounts.create') }}" class="mt-5 inline-flex h-12 items-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition hover:bg-brand-deep active:scale-[.97]">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                    إضافة أول حساب
                </a>
            </div>
        @else
            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($accounts as $account)
                    <li>
                        <a href="{{ route('payment-accounts.edit', $account) }}" @class([
                            'group flex h-full flex-col rounded-2xl border p-5 transition duration-150 hover:-translate-y-0.5 hover:border-rule-strong hover:shadow-lg hover:shadow-ink/5',
                            'border-rule bg-white' => $account->is_active,
                            'border-dashed border-rule-strong bg-sheet/60' => ! $account->is_active,
                        ])>
                            <div class="flex items-center gap-3">
                                <span @class([
                                    'flex size-11 shrink-0 items-center justify-center rounded-xl transition',
                                    'bg-credit-soft text-credit group-hover:bg-credit group-hover:text-white' => $account->is_active,
                                    'bg-paper text-ink-muted' => ! $account->is_active,
                                ])>
                                    <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h4" /></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p @class(['truncate text-lg font-extrabold', 'text-ink-muted' => ! $account->is_active])>{{ $account->name }}</p>
                                    <p class="truncate text-sm text-ink-muted">باسم {{ $account->holder_name }}</p>
                                </div>
                                @unless ($account->is_active)
                                    <span class="shrink-0 rounded-md border border-rule-strong px-2 py-0.5 text-xs font-bold text-ink-muted">مخفي</span>
                                @endunless
                            </div>

                            <p dir="ltr" class="mt-5 border-t border-dashed border-rule pt-4 text-end text-2xl font-extrabold tracking-wide tabular-nums">{{ $account->number }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
