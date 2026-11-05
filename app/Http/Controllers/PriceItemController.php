<?php

namespace App\Http\Controllers;

use App\Http\Requests\PriceItemRequest;
use App\Models\PriceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceItemController extends Controller
{
    /**
     * The price list, grouped by kind, hidden items last in each group.
     */
    public function index(): View
    {
        $items = PriceItem::query()
            ->orderByDesc('is_active')
            ->ordered()
            ->get()
            ->groupBy('category');

        return view('prices.index', ['groups' => $items]);
    }

    /**
     * Show the form for adding an item.
     */
    public function create(Request $request): View
    {
        $category = array_key_exists($request->query('category'), PriceItem::CATEGORIES)
            ? $request->query('category')
            : 'charging';

        return view('prices.create', ['category' => $category]);
    }

    /**
     * Add an item to the price list.
     */
    public function store(PriceItemRequest $request): RedirectResponse
    {
        $item = PriceItem::create($request->validated());

        return redirect()->route('prices.index')
            ->with('status', 'تمت إضافة '.$item->name.'.');
    }

    /**
     * Show the form for editing an item.
     */
    public function edit(PriceItem $price): View
    {
        return view('prices.edit', ['item' => $price]);
    }

    /**
     * Save changes to an item. Old records keep the price they were made with.
     */
    public function update(PriceItemRequest $request, PriceItem $price): RedirectResponse
    {
        $price->update($request->validated());

        return redirect()->route('prices.index')
            ->with('status', 'تم حفظ '.$price->name.'.');
    }

    /**
     * Delete an item that was never used.
     */
    public function destroy(PriceItem $price): RedirectResponse
    {
        if (! $price->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف صنف مستخدم في حركات. يمكنك إخفاؤه بدلاً من ذلك.');
        }

        $price->delete();

        return redirect()->route('prices.index')
            ->with('status', 'تم حذف '.$price->name.'.');
    }

    /**
     * Hide an item from today's work, or show it again.
     */
    public function toggle(PriceItem $price): RedirectResponse
    {
        $price->toggleActive();

        return back()->with('status', $price->is_active
            ? 'أصبح '.$price->name.' ظاهراً في شغل اليوم.'
            : 'أُخفي '.$price->name.' من شغل اليوم.');
    }

    /**
     * Move an item up or down within its kind.
     */
    public function move(PriceItem $price, string $direction): RedirectResponse
    {
        $price->move($direction);

        return back();
    }
}
