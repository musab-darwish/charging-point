@extends('layouts.app')

@section('title', 'الدخول')

@section('content')
    <div class="mx-auto max-w-sm pt-4">
        <h1 class="text-3xl font-extrabold">الدخول</h1>
        <div class="mt-2 h-[7px] w-20 border-y-2 border-debit"></div>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="username" class="block text-sm font-medium">اسم المستخدم</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}"
                       required autofocus autocomplete="username" autocapitalize="none" spellcheck="false"
                       class="mt-1.5 block h-12 w-full rounded-lg border border-rule-strong bg-white px-3 text-lg focus:border-ink focus:ring-2 focus:ring-ink/20 focus:outline-none">
                @error('username')
                    <p class="mt-1.5 text-sm font-medium text-debit">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">كلمة المرور</label>
                <input id="password" name="password" type="password"
                       required autocomplete="current-password"
                       class="mt-1.5 block h-12 w-full rounded-lg border border-rule-strong bg-white px-3 text-lg focus:border-ink focus:ring-2 focus:ring-ink/20 focus:outline-none">
                @error('password')
                    <p class="mt-1.5 text-sm font-medium text-debit">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex h-11 items-center gap-2.5">
                <input type="checkbox" name="remember" value="1" class="size-5 accent-ink">
                <span>تذكرني على هاد الجهاز</span>
            </label>

            <button type="submit" class="h-13 w-full rounded-lg bg-ink text-lg font-bold text-sheet">دخول</button>
        </form>
    </div>
@endsection
