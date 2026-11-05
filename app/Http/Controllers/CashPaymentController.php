<?php

namespace App\Http\Controllers;

use App\Actions\RecordCashPayment;
use App\Http\Requests\CashPaymentRequest;
use App\Support\WorkDay;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class CashPaymentController extends Controller
{
    /**
     * Record a cash payment ("واصل") from the day page or the customer's page.
     */
    public function store(CashPaymentRequest $request, RecordCashPayment $recordPayment): RedirectResponse
    {
        $day = CarbonImmutable::parse($request->validated('date'));
        $customer = $request->customer();

        $recorded = $recordPayment->handle(
            $customer,
            $request->amount(),
            WorkDay::momentOn($day),
            $request->user(),
            $request->validated('form_token'),
        );

        $back = WorkDay::redirectBack($day, $request->validated('back_to_customer'));

        if (! $recorded) {
            return $back->with('status', 'هذه الدفعة مسجّلة من قبل.');
        }

        return $back->with('status', 'تم تسجيل دفعة '.$request->amount().' ₪ من '.$customer->name.'.');
    }
}
