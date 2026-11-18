<?php

namespace App\Http\Controllers;

use App\Actions\RecordPayment;
use App\Http\Requests\PaymentRequest;
use App\Support\WorkDay;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    /**
     * Record a payment ("واصل"), cash or transfer, from the day page, the
     * customer's page or the transfers page ("تسجيل حوالة").
     */
    public function store(PaymentRequest $request, RecordPayment $recordPayment): RedirectResponse
    {
        $day = CarbonImmutable::parse($request->validated('date'));
        $customer = $request->customer();
        $account = $request->account();
        $confirmed = $request->confirmedByOwner();

        $recorded = $recordPayment->handle(
            $customer,
            $request->amount(),
            WorkDay::momentOn($day),
            $request->user(),
            $request->validated('form_token'),
            $account,
            $request->validated('reference'),
            $confirmed,
        );

        $back = $request->backToTransfers()
            ? redirect()->route('transfers.index')
            : WorkDay::redirectBack($day, $request->validated('back_to_customer'));

        if (! $recorded) {
            return $back->with('status', 'هذه الدفعة مسجّلة من قبل.');
        }

        return $back->with('status', match (true) {
            $account && $confirmed => 'تم تسجيل حوالة '.$request->amount().' ₪ من '.$customer->name.' على '.$account->name.'، وتأكد وصولها.',
            (bool) $account => 'تم تسجيل حوالة '.$request->amount().' ₪ من '.$customer->name.' على '.$account->name.'، بانتظار التأكد من وصولها.',
            default => 'تم تسجيل دفعة '.$request->amount().' ₪ من '.$customer->name.'.',
        });
    }
}
