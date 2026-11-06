@extends('layouts.app')

@section('title', 'نقل الديون السابقة')

@php
    $oldCustomer = old('customer_id') ? \App\Models\Customer::find(old('customer_id')) : null;
    $old = [
        'customer' => $oldCustomer ? ['id' => $oldCustomer->id, 'name' => $oldCustomer->name, 'balance' => $oldCustomer->balance()] : null,
        'newCustomer' => old('new_customer_name'),
    ];
    $direction = old('direction', 'owed');
    $error = '<svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg>';
@endphp

@section('content')
    <div class="space-y-6">
        <header>
            <h1 class="text-3xl font-extrabold">نقل الديون السابقة</h1>
            <p class="mt-2 max-w-2xl text-ink-muted">انقل دفتر الدين إلى النظام سطراً سطراً: لكل زبون مبلغ واحد هو ما تبقّى في الدفتر، فيبدأ حسابه منه.</p>
        </header>

        <dl class="stagger grid grid-cols-2 gap-3 lg:grid-cols-3">
            <div class="rounded-2xl border border-rule bg-white p-4">
                <dt class="text-sm font-bold text-ink-muted">زبائن نُقلوا</dt>
                <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ $moved->count() }}</dd>
            </div>
            <div class="rounded-2xl border border-debit/25 bg-debit-soft/60 p-4">
                <dt class="text-sm font-bold text-ink-muted">مجموع ما عليهم</dt>
                <dd class="mt-1 text-3xl font-extrabold tabular-nums text-debit">{{ $owed }} <span class="text-base">₪</span></dd>
            </div>
            <div class="col-span-2 rounded-2xl border border-rule bg-white p-4 lg:col-span-1">
                <dt class="text-sm font-bold text-ink-muted">مجموع ما لهم</dt>
                <dd class="mt-1 text-3xl font-extrabold tabular-nums text-credit">{{ $theirs }} <span class="text-base">₪</span></dd>
            </div>
        </dl>

        <div class="grid items-start gap-6 lg:grid-cols-[24rem_minmax(0,1fr)]">
            {{-- One line of the notebook --}}
            <form method="POST" action="{{ route('old-debts.store') }}" data-submit-lock data-old-debt-form data-old='{{ json_encode($old) }}'
                class="animate-rise rounded-2xl border border-rule bg-white p-5 sm:p-6 lg:sticky lg:top-6">
                @csrf
                <input type="hidden" name="form_token" value="{{ $formToken }}">
                <input type="hidden" name="customer_id" data-customer-id>
                <input type="hidden" name="new_customer_name" data-new-customer>

                <h2 class="text-lg font-extrabold">نقل رصيد من الدفتر</h2>

                <div class="mt-5">
                    <p class="mb-1.5 text-sm font-bold">الزبون</p>
                    @include('partials.customer-picker', ['pickerId' => 'debt', 'next' => '#debt-amount'])
                    <p data-error-for="debt-customer-search" @unless ($errors->hasAny(['customer_id', 'new_customer_name'])) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit">{!! $error !!}<span data-error-text>{{ $errors->first('customer_id') ?: $errors->first('new_customer_name') }}</span></p>
                </div>

                <fieldset class="mt-5">
                    <legend class="mb-1.5 text-sm font-bold">في الدفتر</legend>
                    <div class="grid grid-cols-2 gap-1 rounded-xl bg-paper p-1">
                        <label class="flex h-11 cursor-pointer items-center justify-center rounded-lg font-bold text-ink-muted transition hover:text-ink has-checked:bg-white has-checked:text-debit has-checked:shadow-sm has-focus-visible:ring-2 has-focus-visible:ring-debit/40">
                            <input type="radio" name="direction" value="owed" @checked($direction === 'owed') class="sr-only">
                            عليه
                        </label>
                        <label class="flex h-11 cursor-pointer items-center justify-center rounded-lg font-bold text-ink-muted transition hover:text-ink has-checked:bg-white has-checked:text-credit has-checked:shadow-sm has-focus-visible:ring-2 has-focus-visible:ring-credit/40">
                            <input type="radio" name="direction" value="credit" @checked($direction === 'credit') class="sr-only">
                            له
                        </label>
                    </div>
                </fieldset>

                <label for="debt-amount" class="mb-1.5 mt-5 block text-sm font-bold">المبلغ المتبقي في الدفتر</label>
                <div class="relative" dir="ltr">
                    <input id="debt-amount" name="amount" type="text" inputmode="decimal" autocomplete="off" value="{{ old('amount') }}"
                        @error('amount') aria-invalid="true" @enderror
                        class="block h-16 w-full rounded-xl border border-rule-strong bg-white ps-4 pe-12 text-end text-3xl font-extrabold tabular-nums transition focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15 aria-invalid:border-debit aria-invalid:focus:ring-debit/15">
                    <span class="pointer-events-none absolute inset-y-0 end-4 flex items-center text-xl font-bold text-ink-muted">₪</span>
                </div>
                <p data-error-for="debt-amount" @unless ($errors->has('amount')) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit">{!! $error !!}<span data-error-text>{{ $errors->first('amount') }}</span></p>
                <p data-hint-for="debt-amount" @error('amount') hidden @enderror class="mt-2 text-xs text-ink-muted">المجموع الأخير بعد المشطوب والتحويلات، لا كل الأرقام.</p>

                <x-button class="mt-6 w-full" loading="جارٍ النقل…">نقل الرصيد</x-button>
            </form>

            {{-- What has been moved so far --}}
            <section aria-labelledby="moved-title" class="animate-rise max-lg:pb-20">
                <h2 id="moved-title" class="mb-3 text-lg font-extrabold">ما نُقل حتى الآن <span class="text-sm font-medium text-ink-muted">الأحدث أولاً</span></h2>

                @if ($moved->isEmpty())
                    <div class="ledger-paper rounded-2xl border border-rule py-12 ps-14 pe-6 text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-white text-ink-muted">
                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H19v16H5.5A1.5 1.5 0 0 0 4 20.5z" /><path d="M4 20.5A1.5 1.5 0 0 0 5.5 22H19v-3" /><path d="M8 7h7M8 11h5" /></svg>
                        </span>
                        <p class="mt-4 text-lg font-bold">لم يُنقل أي رصيد بعد</p>
                        <p class="mt-1 text-ink-muted">ابدأ من أول سطر في دفتر الدين.</p>
                    </div>
                @else
                    <ul class="divide-y divide-rule overflow-hidden rounded-2xl border border-rule bg-white">
                        @foreach ($moved as $movement)
                            <li class="flex items-center gap-4 px-4 py-3 sm:px-5">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">{{ mb_substr($movement->customer->name, 0, 1) }}</span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('customers.show', $movement->customer) }}" class="block truncate font-bold transition hover:text-brand">{{ $movement->customer->name }}</a>
                                    <p class="text-xs text-ink-muted">{{ $movement->user?->name }} · {{ $movement->created_at->translatedFormat('j F، H:i') }}</p>
                                </div>
                                @if ($movement->isDebit())
                                    <span class="shrink-0 font-extrabold tabular-nums text-debit">عليه {{ $movement->amount }} ₪</span>
                                @else
                                    <span class="shrink-0 font-extrabold tabular-nums text-credit">له {{ $movement->amount }} ₪</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-sm text-ink-muted">إن نُقل مبلغ خطأ، افتح كشف حساب الزبون وألغِه من «تصحيح خطأ»، ثم انقله من جديد.</p>
                @endif
            </section>
        </div>
    </div>
@endsection
