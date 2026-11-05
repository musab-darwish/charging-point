<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only the owner manages users, the price list and the transfer
        // accounts, and confirms that a transfer arrived.
        Gate::define('manage-users', fn (User $user) => $user->is_owner);
        Gate::define('manage-prices', fn (User $user) => $user->is_owner);
        Gate::define('manage-payment-accounts', fn (User $user) => $user->is_owner);
        Gate::define('verify-transfers', fn (User $user) => $user->is_owner);
    }
}
