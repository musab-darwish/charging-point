{{-- The account message to copy or send on WhatsApp, opened by [data-account-message="<url>"]
     (resources/js/behaviors/account-message.js). The text is fetched when it opens. --}}
<dialog id="account-message" aria-labelledby="account-message-title"
    class="m-auto w-[min(100%-2rem,32rem)] overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45">
    <div class="flex max-h-[calc(100dvh-2rem)] animate-drop flex-col rounded-3xl border border-rule bg-white shadow-2xl shadow-ink/25">
        <div class="flex items-center gap-3 px-6 pt-6 sm:px-7 sm:pt-7">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z" /><path d="M8.5 10.5h7M8.5 14h4" /></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="account-message-title" class="text-xl font-extrabold">رسالة الحساب</h2>
                <p class="truncate text-sm text-ink-muted" data-message-name></p>
            </div>
            <button type="button" data-close-message aria-label="إغلاق" class="flex size-11 shrink-0 items-center justify-center rounded-lg text-ink-muted transition hover:bg-paper">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>

        <div role="tablist" aria-label="نوع الرسالة" class="mx-6 mt-5 grid grid-cols-2 gap-1 rounded-xl bg-paper p-1 sm:mx-7">
            <button type="button" role="tab" data-message-kind="short" aria-selected="true"
                class="h-11 rounded-lg font-bold text-ink-muted transition hover:text-ink aria-selected:bg-white aria-selected:text-brand aria-selected:shadow-sm">مختصرة</button>
            <button type="button" role="tab" data-message-kind="full" aria-selected="false"
                class="h-11 rounded-lg font-bold text-ink-muted transition hover:text-ink aria-selected:bg-white aria-selected:text-brand aria-selected:shadow-sm">كشف كامل</button>
        </div>
        <p class="mx-6 mt-2 text-xs text-ink-muted sm:mx-7" data-message-kind-note>آخر دفعة وما بعدها والمتبقي. أرسل الكشف الكامل إن طلب الزبون التفاصيل.</p>

        <div class="mx-6 mt-4 min-h-0 flex-1 overflow-y-auto rounded-2xl border border-rule bg-sheet sm:mx-7">
            <p data-message-loading class="flex items-center justify-center gap-2 px-5 py-10 text-sm text-ink-muted">
                <svg class="size-5 animate-spin-fast" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                جارٍ تجهيز الرسالة…
            </p>
            <p data-message-failed hidden class="px-5 py-10 text-center text-sm font-medium text-debit">تعذّر تجهيز الرسالة. أغلق النافذة وحاول مرة أخرى.</p>
            <p data-message-text hidden dir="rtl" class="whitespace-pre-wrap px-5 py-4 text-[15px] leading-relaxed tabular-nums"></p>
        </div>

        <div class="px-6 pb-6 pt-5 sm:px-7 sm:pb-7">
            <div class="grid grid-cols-2 gap-3">
                <button type="button" data-copy-message disabled
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border-2 border-rule-strong font-bold transition hover:bg-paper active:scale-[.97] disabled:pointer-events-none disabled:opacity-50">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2" /><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3" /></svg>
                    <span data-copy-label>نسخ</span>
                </button>
                <a data-whatsapp target="_blank" rel="noopener" aria-disabled="true"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-brand font-bold text-white transition hover:bg-brand-deep active:scale-[.97] aria-disabled:pointer-events-none aria-disabled:opacity-50">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z" /></svg>
                    فتح بالواتساب
                </a>
            </div>
            <p data-no-phone hidden class="mt-3 text-xs text-ink-muted">لا يوجد رقم جوال لهذا الزبون، فانسخ الرسالة وأرسلها بنفسك، أو أضف رقمه من «تعديل».</p>
        </div>
    </div>
</dialog>
