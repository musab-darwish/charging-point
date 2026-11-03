<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserPasswordController extends Controller
{
    /**
     * Show the form for changing a user's password.
     */
    public function edit(User $user): View
    {
        return view('users.password', ['user' => $user]);
    }

    /**
     * Set a new password and sign the user out everywhere else,
     * so the old password stops working on every device at once.
     */
    public function update(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return redirect()->route('users.index')
            ->with('status', 'تم تغيير كلمة مرور '.$user->name.'.');
    }
}
