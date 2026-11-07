{{-- On a phone that can, offer the fingerprint sign-in once (until it's set up, or dismissed on this device) --}}
@unless (auth()->user()->hasPasskeysEnabled())
    <div data-passkey-offer hidden class="flex animate-drop flex-wrap items-center gap-x-4 gap-y-3 rounded-2xl border border-brand/30 bg-brand-soft/60 px-4 py-3.5 sm:px-5">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-brand">
            @include('passkeys.partials.icon', ['class' => 'size-5'])
        </span>
        <p class="min-w-0 flex-1 text-sm leading-relaxed">
            <span class="font-bold">ادخل ببصمتك في المرة القادمة.</span>
            <span class="text-ink-muted">بدل كتابة كلمة المرور على الجوال.</span>
        </p>
        <div class="flex w-full gap-2 sm:w-auto">
            <button type="button" data-passkey-offer-dismiss class="inline-flex h-11 flex-1 items-center justify-center rounded-xl px-4 text-sm font-bold text-ink-muted transition hover:bg-white sm:flex-none">ليس الآن</button>
            <a href="{{ route('passkeys.index') }}" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-deep sm:flex-none">تفعيل البصمة</a>
        </div>
    </div>
@endunless
