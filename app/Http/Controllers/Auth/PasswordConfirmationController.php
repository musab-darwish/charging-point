<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Asks for the password again before something sensitive (adding or
 * removing a fingerprint sign-in). Laravel's password.confirm middleware
 * sends people here; a confirmation lasts three hours.
 */
class PasswordConfirmationController extends Controller
{
    public function create(): View
    {
        return view('auth.confirm-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['password' => ['required', 'string', 'current_password']],
            [
                'password.required' => 'اكتب كلمة المرور.',
                'password.current_password' => 'كلمة المرور غير صحيحة.',
            ],
        );

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('day.today'));
    }
}
