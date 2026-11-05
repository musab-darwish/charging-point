{{-- Why a recording or a payment was not saved --}}
@if ($errors->any())
    <div role="alert" class="flex animate-drop items-start gap-3 rounded-2xl bg-debit-soft px-5 py-4 text-debit">
        <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg>
        <div>
            <p class="font-bold">لم يُسجَّل شيء. صحّح ما يلي:</p>
            <ul class="mt-1 list-disc space-y-0.5 ps-5 text-sm font-medium">
                @foreach (array_unique($errors->all()) as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
