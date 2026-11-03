{{-- One shared dialog for every form with data-confirm (see resources/js/behaviors/confirm.js) --}}
<dialog id="confirm-dialog" aria-labelledby="confirm-dialog-title"
    class="m-auto w-[min(100%-2rem,28rem)] rounded-2xl border border-rule bg-white p-0 text-ink shadow-2xl shadow-ink/20 backdrop:bg-ink/40 open:animate-drop">
    <div class="p-6">
        <span class="flex size-11 items-center justify-center rounded-full bg-debit-soft text-debit">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                <path d="M12 8v5M12 16.5v.01" />
                <path d="M10.3 3.9 2.4 17.6A2 2 0 0 0 4.1 20.6h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />
            </svg>
        </span>

        <h2 id="confirm-dialog-title" data-confirm-title class="mt-4 text-xl font-extrabold">تأكيد</h2>
        <p data-confirm-message class="mt-2 leading-relaxed text-ink-muted"></p>
    </div>

    <div class="flex flex-col-reverse gap-2 border-t border-rule bg-sheet px-6 py-4 sm:flex-row">
        <button type="button" data-confirm-accept class="inline-flex h-12 items-center justify-center rounded-xl bg-debit px-5 font-bold text-white transition duration-150 hover:bg-debit/90 active:scale-[.97]">تأكيد</button>
        <button type="button" data-confirm-cancel class="inline-flex h-12 items-center justify-center rounded-xl px-5 font-bold text-ink transition hover:bg-paper">إلغاء</button>
    </div>
</dialog>
