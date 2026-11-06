<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Behind Tailscale's HTTPS the app itself is reached over plain http on
        // the laptop: with an https APP_URL, every link it builds is https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Only the owner manages users, the price list and the transfer
        // accounts, confirms that a transfer arrived, and moves over the
        // debt notebook's balances.
        Gate::define('manage-users', fn (User $user) => $user->is_owner);
        Gate::define('manage-prices', fn (User $user) => $user->is_owner);
        Gate::define('manage-payment-accounts', fn (User $user) => $user->is_owner);
        Gate::define('verify-transfers', fn (User $user) => $user->is_owner);
        Gate::define('move-old-debts', fn (User $user) => $user->is_owner);
    }
}
