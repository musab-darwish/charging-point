<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

/**
 * The owner confirms a transfer arrived on the account ("تأكدت"), or takes
 * that back when it was pressed by mistake.
 */
class TransferVerificationController extends Controller
{
    public function store(Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->isTransfer(), 404);

        $transaction->verify();

        return back()->with('status', 'تم تأكيد وصول حوالة '.$transaction->amount.' ₪.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->isTransfer(), 404);

        $transaction->unverify();

        return back()->with('status', 'أُلغي تأكيد حوالة '.$transaction->amount.' ₪، وعادت «لم تتأكد».');
    }
}
