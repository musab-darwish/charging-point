{{-- One shared dialog for every form with data-confirm (see resources/js/behaviors/confirm.js).
     The animation is on the inner panel: a transform on the <dialog> itself would trap the cursor inside it. --}}
<dialog id="confirm-dialog" aria-labelledby="confirm-dialog-title"
    class="m-auto w-[min(100%-2rem,26rem)] overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45">
    <div class="animate-drop rounded-3xl border border-rule bg-white px-6 pb-6 pt-8 text-center shadow-2xl shadow-ink/25 sm:px-8">
        <span class="mx-auto flex size-18 items-center justify-center rounded-full bg-debit-soft text-debit ring-8 ring-debit-soft/50">
            <svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 9v4M12 16.5v.01" />
                <path d="M10.3 3.9 2.4 17.6A2 2 0 0 0 4.1 20.6h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />
            </svg>
        </span>

        <h2 id="confirm-dialog-title" data-confirm-title class="mt-6 text-2xl font-extrabold">تأكيد</h2>
        <p data-confirm-message class="mx-auto mt-3 max-w-xs leading-relaxed text-ink-muted"></p>

        <div class="mt-8 grid grid-cols-2 gap-3">
            <button type="button" data-confirm-cancel autofocus class="inline-flex h-12 items-center justify-center rounded-xl border-2 border-rule-strong font-bold text-ink transition duration-150 hover:bg-paper active:scale-[.97]">إلغاء</button>
            <button type="button" data-confirm-accept class="inline-flex h-12 items-center justify-center rounded-xl bg-debit px-4 font-bold text-white transition duration-150 hover:bg-debit/90 active:scale-[.97]">تأكيد</button>
        </div>
    </div>
</dialog>
