<?php

namespace App\Http\Controllers;

use App\Actions\RecordOpeningBalance;
use App\Http\Requests\OpeningBalanceRequest;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OldDebtController extends Controller
{
    /**
     * Moving the debt notebook over, line by line: the form, and every
     * balance moved so far (latest first) with the totals.
     */
    public function index(): View
    {
        $moved = Transaction::query()
            ->where('category', Transaction::OPENING)
            ->whereNull('reverses_id')
            ->whereDoesntHave('reversal')
            ->with(['customer', 'user'])
            ->latest('id')
            ->get();

        $total = fn (string $type) => $moved->where('type', $type)
            ->reduce(fn (string $sum, Transaction $movement) => bcadd($sum, $movement->amount, 2), '0.00');

        return view('old-debts.index', [
            'moved' => $moved,
            'owed' => $total(Transaction::DEBIT),
            'theirs' => $total(Transaction::CREDIT),
            'formToken' => (string) Str::uuid(),
        ]);
    }

    /**
     * Move one customer's balance over, adding the customer first when a
     * new name was typed.
     */
    public function store(OpeningBalanceRequest $request, RecordOpeningBalance $record): RedirectResponse
    {
        $existing = $request->customer()?->openingBalance();

        if ($existing) {
            return back()->withInput()->with('error', 'نُقل رصيد '.$existing->customer->name.' السابق من قبل ('.$existing->amount.' ₪). إن كان خطأ، ألغِه من كشف حسابه ثم انقله من جديد.');
        }

        $movement = DB::transaction(function () use ($request, $record) {
            $customer = $request->customer() ?? Customer::create(['name' => $request->validated('new_customer_name')]);

            return $record->handle($customer, $request->amount(), $request->owed(), $request->user(), $request->validated('form_token'));
        });

        if (! $movement) {
            return redirect()->route('old-debts.index')->with('status', 'هذا الرصيد منقول من قبل.');
        }

        return redirect()->route('old-debts.index')->with('status', 'نُقل رصيد '.$movement->customer->name.': '
            .($request->owed() ? 'عليه ' : 'له ').$movement->amount.' ₪.');
    }
}
