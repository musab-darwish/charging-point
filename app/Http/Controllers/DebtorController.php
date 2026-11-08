<?php

namespace App\Http\Controllers;

use App\Support\Debtors;
use App\Support\DebtorsExport;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DebtorController extends Controller
{
    /**
     * Who owes, highest first, and who paid ahead.
     */
    public function index(): View
    {
        return view('debtors.index', ['debtors' => new Debtors]);
    }

    /**
     * The same list as an Excel file (owner only), deleted from the server once sent.
     */
    public function export(): BinaryFileResponse
    {
        $export = new DebtorsExport(new Debtors);

        $path = tempnam(sys_get_temp_dir(), 'debtors');
        $export->writeTo($path);

        return response()->download($path, $export->fileName())->deleteFileAfterSend();
    }
}
