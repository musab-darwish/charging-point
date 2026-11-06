{{-- Show the "×" beside each movement of the nearest [data-correctable], to cancel one entered by mistake --}}
<button type="button" data-toggle-correcting aria-pressed="false"
    class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-debit aria-pressed:text-debit">
    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4z" /><path d="M13.5 6.5l4 4" /></svg>
    <span data-correcting-label>تصحيح خطأ</span>
</button>
