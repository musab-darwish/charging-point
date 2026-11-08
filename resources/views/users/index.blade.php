@extends('layouts.app')

@section('title', 'المستخدمون')

@section('content')
    <div class="stagger space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold">المستخدمون</h1>
                <p class="mt-2 text-ink-muted">من يستطيع الدخول إلى النظام. المستخدم الموقوف لا يستطيع الدخول، وتبقى حركاته مسجّلة باسمه.</p>
            </div>

            <a href="{{ route('users.create') }}" class="inline-flex h-13 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97]">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                إضافة مستخدم
            </a>
        </header>

        <ul class="divide-y divide-rule overflow-hidden rounded-2xl border border-rule bg-white">
            @foreach ($users as $user)
                <li @class(['flex flex-wrap items-center gap-x-4 gap-y-3 px-4 py-4 sm:px-6', 'bg-sheet/60' => $user->isDeactivated()])>
                    <span @class([
                        'flex size-11 shrink-0 items-center justify-center rounded-full text-base font-bold',
                        'bg-brand text-white' => ! $user->isDeactivated(),
                        'bg-rule text-ink-muted' => $user->isDeactivated(),
                    ])>{{ mb_substr($user->name, 0, 1) }}</span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p @class(['truncate text-lg font-bold', 'text-ink-muted' => $user->isDeactivated()])>{{ $user->name }}</p>

                            @if ($user->is_owner)
                                <span class="rounded-md bg-brand px-2 py-0.5 text-xs font-bold text-white">المالك</span>
                            @endif

                            @if ($user->isDeactivated())
                                <span class="rounded-md bg-debit-soft px-2 py-0.5 text-xs font-bold text-debit">موقوف</span>
                            @endif

                            @if ($user->is(auth()->user()))
                                <span class="rounded-md border border-rule-strong px-2 py-0.5 text-xs font-bold text-ink-muted">أنت</span>
                            @endif
                        </div>

                        <p class="mt-0.5 text-sm text-ink-muted">
                            <span dir="ltr">{{ $user->username }}</span>
                            <span class="mx-1.5 text-rule-strong">·</span>
                            أُضيف في {{ $user->created_at->translatedFormat('j F Y') }}
                        </p>
                    </div>

                    <div class="flex w-full gap-2 ps-15 sm:w-auto sm:ps-0">
                        <a href="{{ route('users.password.edit', $user) }}" class="inline-flex h-11 items-center gap-1.5 rounded-xl border border-rule-strong px-3.5 text-sm font-bold transition hover:bg-paper">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" /></svg>
                            كلمة المرور
                        </a>

                        @unless ($user->is_owner)
                            @if ($user->isDeactivated())
                                <form method="POST" action="{{ route('users.activate', $user) }}" data-submit-lock data-stay>
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex h-11 items-center gap-1.5 rounded-xl border border-brand/40 px-3.5 text-sm font-bold text-brand transition hover:bg-brand-soft">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                                        تفعيل
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('users.deactivate', $user) }}" data-submit-lock data-stay
                                    data-confirm-title="إيقاف {{ $user->name }}؟"
                                    data-confirm="لن يستطيع {{ $user->name }} تسجيل الدخول، وسيُسجَّل خروجه فوراً. تبقى كل حركاته مسجّلة باسمه، ويمكنك إعادة تفعيله في أي وقت."
                                    data-confirm-action="إيقاف الحساب">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex h-11 items-center gap-1.5 rounded-xl border border-debit/30 px-3.5 text-sm font-bold text-debit transition hover:bg-debit-soft">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M5.7 5.7l12.6 12.6" /></svg>
                                        إيقاف
                                    </button>
                                </form>
                            @endif
                        @endunless
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
