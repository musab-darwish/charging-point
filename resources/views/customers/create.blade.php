@extends('layouts.app')

@section('title', 'إضافة زبون')

@section('content')
    <div class="stagger">
        <a href="{{ route('customers.index') }}" class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            الزبائن
        </a>

        <header>
            <h1 class="text-3xl font-extrabold">إضافة زبون</h1>
            <p class="mt-2 text-ink-muted">بعد الإضافة يمكنك تسجيل شغله ودفعاته على حسابه.</p>
        </header>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <form method="POST" action="{{ route('customers.store') }}" data-submit-lock class="rounded-2xl border border-rule bg-white p-5 sm:p-7">
                @csrf

                @include('customers.partials.form', ['customer' => null])

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-rule pt-5 sm:flex-row">
                    <x-button class="sm:min-w-44" loading="جارٍ الحفظ…">إضافة الزبون</x-button>
                    <a href="{{ route('customers.index') }}" class="inline-flex h-13 items-center justify-center rounded-xl px-5 font-bold text-ink transition hover:bg-paper">إلغاء</a>
                </div>
            </form>

            <aside class="ledger-paper rounded-2xl border border-rule py-6 ps-14 pe-6">
                <h2 class="font-extrabold">أسماء متشابهة؟</h2>
                <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                    لا يُحفظ اسمان متطابقان، حتى لا يُسجَّل حساب شخص على غيره. إن وُجد زبون بالاسم نفسه، أضف ما يميّزه:
                </p>
                <ul class="mt-3 space-y-1.5 text-sm font-bold">
                    <li>أبو محمد <span class="text-brand">الطويل</span></li>
                    <li>أحمد <span class="text-brand">صاحب الفرن</span></li>
                    <li>سامي <span class="text-brand">الحارة الشرقية</span></li>
                </ul>
            </aside>
        </div>
    </div>
@endsection
