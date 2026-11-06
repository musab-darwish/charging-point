<?php

namespace App\Http\Controllers;

use App\Actions\PickUpFreezeItem;
use App\Actions\StoreFreezeItem;
use App\Http\Requests\PickUpFreezeItemRequest;
use App\Http\Requests\StoreFreezeItemRequest;
use App\Models\Customer;
use App\Models\FreezeItem;
use App\Models\PriceItem;
use App\Support\WorkDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FreezeItemController extends Controller
{
    /**
     * What is in the freezer now (longest-waiting first), and what was
     * picked up in the last week.
     */
    public function index(): View
    {
        return view('freezer.index', [
            'items' => FreezeItem::inFreezer()->with(['customer', 'user'])->get(),
            'recent' => FreezeItem::recentlyPickedUp()->with(['customer', 'pickedUpBy', 'transaction'])->get(),
            // The freezing items on the price list, as quick descriptions.
            'suggestions' => PriceItem::active()->where('category', 'freezing')->ordered()->pluck('name'),
            'storeToken' => (string) Str::uuid(),
            'pickUpToken' => (string) Str::uuid(),
        ]);
    }

    /**
     * Put something in the freezer, adding the customer first when a new
     * name was typed.
     */
    public function store(StoreFreezeItemRequest $request, StoreFreezeItem $storeItem): RedirectResponse
    {
        $item = DB::transaction(function () use ($request, $storeItem) {
            $customer = $request->customer() ?? Customer::create(['name' => $request->validated('new_customer_name')]);

            return $storeItem->handle(
                $customer,
                $request->validated('description'),
                $request->validated('quantity') ? (int) $request->validated('quantity') : null,
                WorkDay::momentOn($request->storedOn()),
                $request->user(),
                $request->validated('form_token'),
            );
        });

        if (! $item) {
            return redirect()->route('freezer.index')->with('status', 'هذا الغرض مسجّل من قبل.');
        }

        return redirect()->route('freezer.index')
            ->with('status', 'وُضع '.$item->label().' في الفريزر باسم '.$item->customer->name.'.');
    }

    /**
     * Hand an item back, charging the price written now.
     */
    public function pickUp(PickUpFreezeItemRequest $request, FreezeItem $freezeItem, PickUpFreezeItem $pickUp): RedirectResponse
    {
        $price = $request->price();

        if (! $pickUp->handle($freezeItem, $price, $request->boolean('pay_now'), $request->user(), $request->validated('form_token'))) {
            return redirect()->route('freezer.index')->with('status', 'هذا الغرض مُسلَّم من قبل.');
        }

        $who = $freezeItem->label().' إلى '.$freezeItem->customer->name;

        return redirect()->route('freezer.index')->with('status', match (true) {
            bccomp($price, '0', 2) <= 0 => 'تم تسليم '.$who.' بدون مقابل.',
            $request->boolean('pay_now') => 'تم تسليم '.$who.' بـ'.$price.' ₪ واصلاً.',
            default => 'تم تسليم '.$who.'، وسُجّل '.$price.' ₪ على حسابه.',
        });
    }

    /**
     * Remove an item entered by mistake, while it is still in the freezer.
     */
    public function destroy(FreezeItem $freezeItem): RedirectResponse
    {
        if (! $freezeItem->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف غرض بعد تسليمه.');
        }

        $freezeItem->delete();

        return redirect()->route('freezer.index')
            ->with('status', 'حُذف '.$freezeItem->label().' من الفريزر.');
    }
}
