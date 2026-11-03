@php
    $messages = array_filter([
        'success' => session('status'),
        'error' => session('error'),
    ]);
@endphp

@if ($messages)
    <div class="pointer-events-none fixed inset-x-0 top-3 z-50 flex flex-col items-center gap-2 px-4">
        @foreach ($messages as $kind => $message)
            <div data-flash role="{{ $kind === 'error' ? 'alert' : 'status' }}" @class([
                'pointer-events-auto flex w-full max-w-md animate-drop items-center gap-3 rounded-xl py-2 ps-4 pe-2 text-[15px] font-bold shadow-lg shadow-ink/10',
                'bg-ink text-sheet' => $kind === 'success',
                'bg-debit-soft text-debit' => $kind === 'error',
            ])>
                @if ($kind === 'success')
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12l5 5 9-10" />
                    </svg>
                @else
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7.5v5.5M12 16.5v.01" />
                    </svg>
                @endif

                <p class="flex-1 py-1">{{ $message }}</p>

                <button type="button" data-flash-close aria-label="إغلاق" class="flex size-10 shrink-0 items-center justify-center rounded-lg opacity-70 transition hover:opacity-100">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
