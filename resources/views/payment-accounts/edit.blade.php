@extends('layouts.app')

@section('title', $account->name)

@section('content')
    <div class="stagger">
        <a href="{{ route('payment-accounts.index') }}" class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            حسابات التحويل
        </a>

        <header>
            <h1 class="text-3xl font-extrabold">تعديل حساب {{ $account->name }}</h1>
            <p class="mt-2 text-ink-muted">
                {{ $account->is_active ? 'متاح عند تسجيل الحوالات.' : 'مخفي من الحوالات الجديدة.' }}
            </p>
        </header>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <form method="POST" action="{{ route('payment-accounts.update', $account) }}" data-submit-lock class="rounded-2xl border border-rule bg-white p-5 sm:p-7">
                @csrf
                @method('PUT')

                @include('payment-accounts.partials.form')

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-rule pt-5 sm:flex-row">
                    <x-button class="sm:min-w-44" loading="جارٍ الحفظ…">حفظ التعديلات</x-button>
                    <a href="{{ route('payment-accounts.index') }}" class="inline-flex h-13 items-center justify-center rounded-xl px-5 font-bold text-ink transition hover:bg-paper">إلغاء</a>
                </div>
            </form>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-rule bg-white p-6">
                    <h2 class="font-extrabold">{{ $account->is_active ? 'إخفاء الحساب' : 'إظهار الحساب' }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                        {{ $account->is_active
                            ? 'لا يظهر عند تسجيل حوالة جديدة ولا في رسالة الحساب، وتبقى الحوالات المسجّلة عليه كما هي.'
                            : 'يعود متاحاً عند تسجيل الحوالات وفي رسالة الحساب.' }}
                    </p>
                    <form method="POST" action="{{ route('payment-accounts.toggle', $account) }}" class="mt-4" data-submit-lock data-stay>
                        @csrf
                        @method('PATCH')
                        <button type="submit" @class([
                            'inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-xl border text-sm font-bold transition',
                            'border-rule-strong text-ink hover:bg-paper' => $account->is_active,
                            'border-brand/40 text-brand hover:bg-brand-soft' => ! $account->is_active,
                        ])>
                            {{ $account->is_active ? 'إخفاء من الحوالات' : 'إظهار في الحوالات' }}
                        </button>
                    </form>
                </div>

                @if ($account->canBeDeleted())
                    <div class="rounded-2xl border border-rule bg-white p-6">
                        <h2 class="font-extrabold">حذف الحساب</h2>
                        <p class="mt-3 text-sm leading-relaxed text-ink-muted">لم تُسجَّل عليه أي حوالة بعد، لذلك يمكن حذفه.</p>
                        <form method="POST" action="{{ route('payment-accounts.destroy', $account) }}" class="mt-4" data-submit-lock
                            data-confirm-title="حذف حساب {{ $account->name }}؟"
                            data-confirm="سيُحذف الحساب نهائياً."
                            data-confirm-action="حذف نهائي">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-1.5 rounded-xl border border-debit/30 text-sm font-bold text-debit transition hover:bg-debit-soft">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" /></svg>
                                حذف الحساب
                            </button>
                        </form>
                    </div>
                @else
                    <div class="rounded-2xl border border-rule bg-white p-6">
                        <h2 class="font-extrabold">حذف الحساب</h2>
                        <p class="mt-3 text-sm leading-relaxed text-ink-muted">سُجّلت عليه حوالات، لذلك لا يمكن حذفه حتى تبقى سجلاتها كاملة. يمكنك إخفاؤه بدلاً من ذلك.</p>
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection
