<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List the system's users, the owner first, deactivated ones last.
     */
    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('is_owner')
            ->orderByRaw('deactivated_at is not null')
            ->orderBy('name')
            ->get();

        return view('users.index', ['users' => $users]);
    }
}
