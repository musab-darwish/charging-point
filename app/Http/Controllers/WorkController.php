<?php

namespace App\Http\Controllers;

use App\Actions\AddOneTimeCustomer;
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
     * when a new name was typed, or a one-time customer when the walk-in
     * was named for one visit.
     */
    public function store(RecordWorkRequest $request, RecordWork $recordWork, AddOneTimeCustomer $addOneTime): RedirectResponse
    {
        $day = $request->day();

        if ($request->alreadyRecorded()) {
            return WorkDay::redirectBack($day, $request->validated('back_to_customer'))->with('status', 'هذا الشغل مسجّل من قبل.');
        }

        $customer = DB::transaction(function () use ($request, $recordWork, $addOneTime, $day) {
            $customer = match (true) {
                $request->isOneTime() => $addOneTime->handle($request->validated('one_time_name'), $request->oneTimePhone(), $day),
                default => $request->customer() ?? Customer::create(['name' => $request->validated('new_customer_name')]),
            };

            $recorded = $recordWork->handle(
                $customer,
                $request->items(),
                WorkDay::momentOn($day),
                $request->boolean('pay_now'),
                $request->user(),
                $request->validated('form_token'),
            );

            // The same form arrived twice at once and the other one won: don't
            // leave behind the customer this one added, with nothing on them.
            if (! $recorded) {
                if ($customer->wasRecentlyCreated) {
                    $customer->delete();
                }

                return null;
            }

            return $customer;
        });

        if (! $customer) {
            return WorkDay::redirectBack($day, $request->validated('back_to_customer'))->with('status', 'هذا الشغل مسجّل من قبل.');
        }

        return WorkDay::redirectBack($day, $request->validated('back_to_customer'))->with('status', $request->boolean('pay_now')
            ? 'تم تسجيل شغل '.$customer->name.' واصلاً.'
            : 'تم تسجيل شغل '.$customer->name.' على حسابه.');
    }
}
