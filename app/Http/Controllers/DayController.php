<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\PriceItem;
use App\Support\DaySheet;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DayController extends Controller
{
    /**
     * Today's work: the main screen.
     */
    public function today(): View
    {
        return $this->render(CarbonImmutable::today());
    }

    /**
     * An earlier day, like turning back the pages of the daily notebook.
     */
    public function show(string $date): View|RedirectResponse
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        abort_unless($day && $day->format('Y-m-d') === $date, 404);

        if ($day->isToday() || $day->isFuture()) {
            return redirect()->route('day.today');
        }

        return $this->render($day);
    }

    private function render(CarbonImmutable $day): View
    {
        return view('day.show', [
            'sheet' => new DaySheet($day),
            'priceGroups' => PriceItem::active()->ordered()->get()->groupBy('category'),
            'walkIn' => Customer::walkIn(),
            'workToken' => (string) Str::uuid(),
            'paymentToken' => (string) Str::uuid(),
            'paymentAccounts' => PaymentAccount::active()->ordered()->get(),
        ]);
    }
}
