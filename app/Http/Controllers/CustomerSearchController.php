<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerSearchController extends Controller
{
    /**
     * Customers for the day page's picker, with their balance. With no
     * search term, the customers seen most recently come first.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();

        $customers = Customer::regular()
            ->search($term)
            ->withMax('transactions', 'occurred_at')
            ->when($term === '', fn ($query) => $query->orderByDesc('transactions_max_occurred_at'))
            ->orderBy('name')
            ->limit(8)
            ->get();

        return response()->json($customers->map(fn (Customer $customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone_display,
            'balance' => $customer->balance(),
        ]));
    }
}
