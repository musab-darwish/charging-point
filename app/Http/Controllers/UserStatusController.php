<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserStatusController extends Controller
{
    /**
     * Stop a user from logging in. The owner can never be deactivated,
     * or nobody would be left to manage the system.
     */
    public function deactivate(User $user): RedirectResponse
    {
        if ($user->is_owner) {
            return back()->with('error', 'لا يمكن إيقاف حساب المالك.');
        }

        $user->deactivate();

        return back()->with('status', 'تم إيقاف '.$user->name.'. لن يستطيع الدخول حتى تعيد تفعيله.');
    }

    /**
     * Let a deactivated user log in again.
     */
    public function activate(User $user): RedirectResponse
    {
        $user->activate();

        return back()->with('status', 'تم تفعيل '.$user->name.'. يستطيع الدخول الآن.');
    }
}
