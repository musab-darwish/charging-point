<?php

namespace App\Http\Controllers;

use App\Models\PaymentAccount;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransferController extends Controller
{
    /**
     * Transfers not confirmed yet, grouped by the account they came on (so
     * the owner checks one app at a time), the oldest first in each.
     */
    public function index(): View
    {
        $transfers = Transaction::awaitingConfirmation()
            ->with(['customer', 'paymentAccount', 'user'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $sum = fn (Collection $group) => $group->reduce(fn (string $total, Transaction $transfer) => bcadd($total, $transfer->amount, 2), '0.00');

        return view('transfers.index', [
            // For «تسجيل حوالة»: the accounts a transfer can arrive on, and a fresh form token.
            'paymentAccounts' => PaymentAccount::active()->ordered()->get(),
            'transferToken' => (string) Str::uuid(),
            'groups' => $transfers->groupBy('payment_account_id')
                ->map(fn (Collection $group) => ['account' => $group->first()->paymentAccount, 'transfers' => $group, 'total' => $sum($group)])
                ->sortBy(fn (array $group) => $group['account']?->name)
                ->values(),
            'count' => $transfers->count(),
            'total' => $sum($transfers),
        ]);
    }
}
