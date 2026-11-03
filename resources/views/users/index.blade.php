@extends('layouts.app')

@section('title', 'المستخدمون')

@section('content')
    <div class="stagger space-y-6">
        <header>
            <h1 class="text-3xl font-extrabold">المستخدمون</h1>
            <p class="mt-2 text-ink-muted">من يستطيع الدخول إلى النظام. المستخدم الموقوف لا يستطيع الدخول، وتبقى حركاته مسجّلة باسمه.</p>
        </header>

        <ul class="divide-y divide-rule overflow-hidden rounded-2xl border border-rule bg-white">
            @foreach ($users as $user)
                <li @class(['flex items-center gap-4 px-4 py-4 sm:px-6', 'bg-sheet/60' => $user->isDeactivated()])>
                    <span @class([
                        'flex size-11 shrink-0 items-center justify-center rounded-full text-base font-bold',
                        'bg-ink text-sheet' => ! $user->isDeactivated(),
                        'bg-rule text-ink-muted' => $user->isDeactivated(),
                    ])>{{ mb_substr($user->name, 0, 1) }}</span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p @class(['truncate text-lg font-bold', 'text-ink-muted' => $user->isDeactivated()])>{{ $user->name }}</p>

                            @if ($user->is_owner)
                                <span class="rounded-md bg-ink px-2 py-0.5 text-xs font-bold text-sheet">المالك</span>
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
                </li>
            @endforeach
        </ul>
    </div>
@endsection
