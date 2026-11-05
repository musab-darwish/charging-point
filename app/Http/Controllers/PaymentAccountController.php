<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentAccountRequest;
use App\Models\PaymentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentAccountController extends Controller
{
    /**
     * The accounts customers transfer to, hidden ones last.
     */
    public function index(): View
    {
        return view('payment-accounts.index', [
            'accounts' => PaymentAccount::query()->orderByDesc('is_active')->ordered()->get(),
        ]);
    }

    /**
     * Show the form for adding an account.
     */
    public function create(): View
    {
        return view('payment-accounts.create');
    }

    /**
     * Add an account.
     */
    public function store(PaymentAccountRequest $request): RedirectResponse
    {
        $account = PaymentAccount::create($request->validated());

        return redirect()->route('payment-accounts.index')
            ->with('status', 'تمت إضافة حساب '.$account->name.'.');
    }

    /**
     * Show the form for editing an account.
     */
    public function edit(PaymentAccount $account): View
    {
        return view('payment-accounts.edit', ['account' => $account]);
    }

    /**
     * Save changes to an account. Transfers already recorded stay linked to it.
     */
    public function update(PaymentAccountRequest $request, PaymentAccount $account): RedirectResponse
    {
        $account->update($request->validated());

        return redirect()->route('payment-accounts.index')
            ->with('status', 'تم حفظ حساب '.$account->name.'.');
    }

    /**
     * Delete an account no transfer was recorded on.
     */
    public function destroy(PaymentAccount $account): RedirectResponse
    {
        if (! $account->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف حساب سُجّلت عليه حوالات. يمكنك إخفاؤه بدلاً من ذلك.');
        }

        $account->delete();

        return redirect()->route('payment-accounts.index')
            ->with('status', 'تم حذف حساب '.$account->name.'.');
    }

    /**
     * Stop offering an account for new transfers, or offer it again.
     */
    public function toggle(PaymentAccount $account): RedirectResponse
    {
        $account->toggleActive();

        return back()->with('status', $account->is_active
            ? 'أصبح حساب '.$account->name.' متاحاً للحوالات.'
            : 'أُخفي حساب '.$account->name.' من الحوالات الجديدة.');
    }
}
