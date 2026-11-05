<?php

namespace App\Http\Controllers;

use App\Actions\RecordWork;
use App\Http\Requests\RecordWorkRequest;
use App\Models\Customer;
use App\Support\WorkDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class WorkController extends Controller
{
    /**
     * Record a customer's items on the day page, adding the customer first
     * when a new name was typed.
     */
    public function store(RecordWorkRequest $request, RecordWork $recordWork): RedirectResponse
    {
        $day = $request->day();

        if ($request->alreadyRecorded()) {
            return WorkDay::redirect($day)->with('status', 'هذا الشغل مسجّل من قبل.');
        }

        $customer = DB::transaction(function () use ($request, $recordWork, $day) {
            $customer = $request->customer() ?? Customer::create(['name' => $request->validated('new_customer_name')]);

            $recordWork->handle(
                $customer,
                $request->items(),
                WorkDay::momentOn($day),
                $request->boolean('pay_now'),
                $request->user(),
                $request->validated('form_token'),
            );

            return $customer;
        });

        return WorkDay::redirect($day)->with('status', $request->boolean('pay_now')
            ? 'تم تسجيل شغل '.$customer->name.' واصلاً.'
            : 'تم تسجيل شغل '.$customer->name.' على حسابه.');
    }
}
