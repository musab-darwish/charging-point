<?php

namespace App\Actions;

use App\Models\Customer;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Add a customer for one visit (they left a phone to charge, something to
 * cool, and pay when they collect it). Names are unique, and a stranger
 * often shares one with a regular, so a taken name gets the day added
 * instead of turning the customer away: "أحمد" becomes "أحمد (8-11)", and
 * "أحمد (8-11) 2" if even that is taken.
 */
class AddOneTimeCustomer
{
    public function handle(string $name, ?string $phone, CarbonInterface $day): Customer
    {
        $customer = new Customer(['name' => $this->freeName($name, $day), 'phone' => $phone]);
        $customer->is_one_time = true;
        $customer->save();

        return $customer;
    }

    private function freeName(string $name, CarbonInterface $day): string
    {
        if (! Customer::where('name', $name)->exists()) {
            return $name;
        }

        $dated = Str::limit($name, 85, '').' ('.$day->format('j-n').')';
        $candidate = $dated;

        for ($number = 2; Customer::where('name', $candidate)->exists(); $number++) {
            $candidate = $dated.' '.$number;
        }

        return $candidate;
    }
}
