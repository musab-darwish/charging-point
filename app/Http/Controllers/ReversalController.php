<?php

namespace App\Http\Controllers;

use App\Actions\ReverseTransaction;
use App\Http\Requests\ReverseTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

class ReversalController extends Controller
{
    /**
     * Cancel a movement from the day page or the customer's page, and go
     * back to where it was done.
     */
    public function store(ReverseTransactionRequest $request, Transaction $transaction, ReverseTransaction $reverse): RedirectResponse
    {
        if (! $reverse->handle($transaction, $request->validated('reason'), $request->user(), $request->validated('form_token'))) {
            return back()->with('error', 'لا يمكن إلغاء هذه الحركة: أُلغيت من قبل، أو هي نفسها إلغاء لحركة أخرى.');
        }

        $what = $transaction->isDebit()
            ? ($transaction->description ?? 'البند')
            : ($transaction->isTransfer() ? 'الحوالة' : 'الدفعة');

        return back()->with('status', 'أُلغيت «'.$what.'» بمبلغ '.$transaction->amount.' ₪ من حساب '.$transaction->customer->name.'.'
            .($transaction->isDebit() && $transaction->freeze_item_id ? ' وعاد الغرض إلى الفريزر.' : ''));
    }
}
