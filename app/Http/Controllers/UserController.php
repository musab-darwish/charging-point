<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Show the form for adding a user.
     */
    public function create(): View
    {
        return view('users.create');
    }

    /**
     * Add a user. New users are never owners.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only(['name', 'username', 'password']));

        return redirect()->route('users.index')
            ->with('status', 'تمت إضافة المستخدم '.$user->name.'.');
    }
}
