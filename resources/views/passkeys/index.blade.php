@extends('layouts.app')

@section('title', 'الدخول بالبصمة')

@section('content')
    <div class="stagger mx-auto max-w-2xl space-y-6">
        <header>
            <h1 class="text-3xl font-extrabold">الدخول بالبصمة</h1>
            <p class="mt-2 leading-relaxed text-ink-muted">ادخل من جوالك ببصمتك بدل كتابة كلمة المرور. تبقى البصمة على جهازك ولا تصل إلى النظام، وتبقى كلمة المرور صالحة دائماً.</p>
        </header>

        {{-- Add this device (resources/js/behaviors/passkeys.js) --}}
        <section data-passkey-register class="ledger-paper rounded-2xl border border-rule py-6 ps-14 pe-5 sm:pe-6">
            <h2 class="font-extrabold">هذا الجهاز</h2>
            <p class="mt-1 text-sm leading-relaxed text-ink-muted">اضغط الزر وضع إصبعك على الحساس، فتدخل بها في المرة القادمة من صفحة الدخول.</p>

            <button type="button" data-passkey-add
                class="mt-5 inline-flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition hover:bg-brand-deep active:scale-[.97] disabled:pointer-events-none disabled:opacity-50 sm:w-auto">
                @include('passkeys.partials.icon', ['class' => 'size-5'])
                <span data-passkey-add-label>تفعيل البصمة</span>
            </button>

            <p data-passkey-message hidden role="status" class="mt-3 text-sm font-medium"></p>
            <p data-passkey-unsupported hidden class="mt-3 text-sm font-medium text-debit">هذا المتصفح لا يدعم الدخول بالبصمة. جرّب من متصفح الجوال (Chrome أو Safari) عبر العنوان الآمن (https).</p>
        </section>

        <section aria-labelledby="devices-title">
            <h2 id="devices-title" class="mb-3 text-lg font-extrabold">الأجهزة المفعّلة</h2>

            @if ($passkeys->isEmpty())
                <p class="rounded-2xl border border-dashed border-rule-strong bg-white/60 px-5 py-8 text-center text-ink-muted">لم تُفعَّل البصمة على أي جهاز بعد.</p>
            @else
                <ul class="divide-y divide-rule overflow-hidden rounded-2xl border border-rule bg-white">
                    @foreach ($passkeys as $passkey)
                        <li class="flex items-center gap-3 px-4 py-3 sm:px-5">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand">
                                @include('passkeys.partials.icon', ['class' => 'size-5'])
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold">{{ $passkey->name }}</p>
                                <p class="text-xs text-ink-muted">
                                    أُضيف {{ $passkey->created_at->translatedFormat('j F Y') }}
                                    · {{ $passkey->last_used_at ? 'آخر دخول '.$passkey->last_used_at->translatedFormat('j F، H:i') : 'لم يُستخدم بعد' }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('passkey.destroy', $passkey) }}" data-submit-lock
                                data-confirm-title="إزالة البصمة من {{ $passkey->name }}؟"
                                data-confirm="لن تستطيع الدخول بالبصمة من هذا الجهاز، وتبقى كلمة المرور كما هي. استخدم هذا إن ضاع الجهاز أو تغيّر."
                                data-confirm-action="إزالة">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="إزالة البصمة من {{ $passkey->name }}"
                                    class="flex size-11 items-center justify-center rounded-xl border border-rule-strong text-ink-muted transition hover:border-debit/40 hover:bg-debit-soft hover:text-debit">
                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" /></svg>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
