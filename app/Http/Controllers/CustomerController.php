<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\PriceItem;
use App\Support\Statement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * List customers, filtered by the search box. The live search asks
     * for the list alone, without the rest of the page.
     */
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $customers = Customer::regular()
            ->search($search)
            ->orderBy('name')
            ->get();

        $view = $request->ajax() ? 'customers.partials.list' : 'customers.index';

        return view($view, [
            'customers' => $customers,
            'search' => $search,
            'total' => Customer::regular()->count(),
        ]);
    }

    /**
     * Show the form for adding a customer.
     */
    public function create(Request $request): View
    {
        // Coming from a search with no results: start with what was typed.
        return view('customers.create', ['name' => $request->string('name')->toString()]);
    }

    /**
     * Add a customer.
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->customerData());

        return redirect()->route('customers.index')
            ->with('status', 'تمت إضافة الزبون '.$customer->name.'.');
    }

    /**
     * A customer's page: the balance and the statement by day, with the
     * day page's dialogs to record work or a payment for them right here.
     */
    public function show(Customer $customer): View
    {
        abort_if($customer->is_walk_in, 404);

        return view('customers.show', [
            'customer' => $customer,
            'statement' => new Statement($customer),
            'priceGroups' => PriceItem::active()->ordered()->get()->groupBy('category'),
            'walkIn' => Customer::walkIn(),
            'workToken' => (string) Str::uuid(),
            'paymentToken' => (string) Str::uuid(),
        ]);
    }

    /**
     * Show the form for editing a customer.
     */
    public function edit(Customer $customer): View
    {
        abort_if($customer->is_walk_in, 404);

        return view('customers.edit', ['customer' => $customer]);
    }

    /**
     * Save changes to a customer.
     */
    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        abort_if($customer->is_walk_in, 404);

        $customer->update($request->customerData());

        return redirect()->route('customers.show', $customer)
            ->with('status', 'تم حفظ بيانات '.$customer->name.'.');
    }

    /**
     * Delete a customer who has no records yet (added by mistake).
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        abort_if($customer->is_walk_in, 404);

        if (! $customer->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف زبون له حركات مسجّلة.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('status', 'تم حذف الزبون '.$customer->name.'.');
    }
}
