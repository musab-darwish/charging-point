<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PasskeyController extends Controller
{
    /**
     * The devices that can sign in to this account with a fingerprint, and
     * adding this one. Adding and removing go through laravel/passkeys.
     */
    public function index(Request $request): View
    {
        return view('passkeys.index', [
            'passkeys' => $request->user()->passkeys()->latest()->get(),
        ]);
    }
}
