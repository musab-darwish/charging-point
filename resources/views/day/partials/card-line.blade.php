{{-- One movement on a customer's card or statement: an item, or a payment in blue.
     A transfer also shows its number and whether it was confirmed ("تأكدت").
     A cancelled one is struck through with why; in correction mode each other one has a "×" to cancel it. --}}
@php
    $label = match (true) {
        $line->category === \App\Models\Transaction::OPENING => $line->description.($line->isDebit() ? '' : ' (له)'),
        $line->isDebit() => $line->description.($line->quantity > 1 ? ' ×'.$line->quantity : ''),
        $line->isTransfer() => 'حوالة على '.$line->paymentAccount?->name,
        default => 'دفعة نقدية',
    };
    $signed = ($line->isDebit() ? '' : '−').$line->amount;
@endphp

@if ($line->isReversed())
    <li class="flex items-baseline gap-3 py-2 text-ink-muted">
        <span class="w-10 shrink-0 text-xs tabular-nums">{{ $line->occurred_at->format('H:i') }}</span>
        <div class="min-w-0 flex-1">
            <p class="truncate line-through decoration-debit/70">{{ $label }}</p>
            <p class="mt-0.5 text-xs leading-relaxed">
                <span class="font-bold text-debit">أُلغيت</span>
                · {{ $line->reversal->description }}
                @if ($line->reversal->user)
                    · {{ $line->reversal->user->name }}
                @endif
            </p>
        </div>
        <span class="tabular-nums line-through decoration-debit/70">{{ $signed }}</span>
    </li>
@else
    <li @class(['flex items-baseline gap-3 py-2', 'text-credit' => ! $line->isDebit()])>
        <span @class(['w-10 shrink-0 text-xs tabular-nums', 'text-ink-muted' => $line->isDebit(), 'opacity-70' => ! $line->isDebit()])>{{ $line->occurred_at->format('H:i') }}</span>

        @if ($line->isTransfer())
            <div class="min-w-0 flex-1">
                <p class="truncate font-medium">{{ $label }}</p>

                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs">
                    @if ($line->reference)
                        <span class="font-bold tabular-nums text-ink-muted">رقم <span dir="ltr">{{ $line->reference }}</span></span>
                    @endif

                    @if ($line->isVerified())
                        @can('verify-transfers')
                            <form method="POST" action="{{ route('transfers.unverify', $line) }}" data-submit-lock
                                data-confirm-title="إلغاء تأكيد الحوالة؟"
                                data-confirm="ستعود حوالة {{ $line->amount }} ₪ «لم تتأكد». لا يتغيّر المبلغ ولا الرصيد."
                                data-confirm-action="إلغاء التأكيد">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="تأكدت {{ $line->verified_at->translatedFormat('j F، H:i') }}. اضغط لإلغاء التأكيد."
                                    class="inline-flex h-11 items-center gap-1 rounded-lg bg-brand-soft px-2.5 font-bold text-brand transition hover:bg-brand-soft/70">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                                    تأكدت
                                </button>
                            </form>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-brand-soft px-2 py-0.5 font-bold text-brand">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                                تأكدت
                            </span>
                        @endcan
                    @else
                        <span class="rounded-md border border-dashed border-credit/50 px-2 py-0.5 font-bold">لم تتأكد</span>

                        @can('verify-transfers')
                            <form method="POST" action="{{ route('transfers.verify', $line) }}" data-submit-lock>
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex h-11 items-center gap-1 rounded-lg border border-credit/40 px-3 text-sm font-bold transition hover:bg-credit-soft active:scale-[.97]">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                                    تأكدت
                                </button>
                            </form>
                        @endcan
                    @endif
                </div>
            </div>
        @elseif ($line->isDebit() && $line->category !== \App\Models\Transaction::OPENING)
            <span class="min-w-0 flex-1 truncate">
                {{ $line->description }}
                @if ($line->quantity > 1)
                    <span class="text-sm font-bold text-ink-muted">×{{ $line->quantity }}</span>
                @endif
            </span>
        @else
            <span class="min-w-0 flex-1 truncate font-medium">{{ $label }}</span>
        @endif

        <span class="font-bold tabular-nums">{{ $signed }}</span>

        <button type="button" aria-label="إلغاء {{ $label }}"
            data-reverse='{{ json_encode([
                'url' => route('transactions.reverse', $line),
                'id' => $line->id,
                'label' => $label,
                'amount' => $line->amount,
                'transfer' => $line->isTransfer(),
            ]) }}'
            class="hidden size-11 shrink-0 items-center justify-center self-center rounded-lg border border-debit/30 text-debit transition hover:bg-debit-soft group-data-[correcting=true]/card:flex">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
    </li>
@endif
