<?php

namespace App\Http\Controllers;

use App\Support\Debtors;
use Illuminate\View\View;

class DebtorController extends Controller
{
    /**
     * Who owes, highest first, and who paid ahead.
     */
    public function index(): View
    {
        return view('debtors.index', ['debtors' => new Debtors]);
    }
}
