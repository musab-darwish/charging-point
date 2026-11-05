<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashPaymentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerSearchController;
use App\Http\Controllers\DayController;
use App\Http\Controllers\PriceItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DayController::class, 'today'])->name('day.today');
    Route::get('/day/{date}', [DayController::class, 'show'])->where('date', '\d{4}-\d{2}-\d{2}')->name('day.show');

    Route::post('/work', [WorkController::class, 'store'])->name('work.store');
    Route::post('/payments/cash', [CashPaymentController::class, 'store'])->name('payments.cash');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/customers/search', CustomerSearchController::class)->name('customers.search');
    Route::resource('customers', CustomerController::class);

    Route::middleware('can:manage-users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');

        Route::get('/users/{user}/password', [UserPasswordController::class, 'edit'])->name('users.password.edit');
        Route::put('/users/{user}/password', [UserPasswordController::class, 'update'])->name('users.password.update');

        Route::patch('/users/{user}/deactivate', [UserStatusController::class, 'deactivate'])->name('users.deactivate');
        Route::patch('/users/{user}/activate', [UserStatusController::class, 'activate'])->name('users.activate');
    });

    Route::middleware('can:manage-prices')->group(function () {
        Route::resource('prices', PriceItemController::class)->except('show');
        Route::patch('/prices/{price}/toggle', [PriceItemController::class, 'toggle'])->name('prices.toggle');
        Route::patch('/prices/{price}/move/{direction}', [PriceItemController::class, 'move'])
            ->whereIn('direction', ['up', 'down'])
            ->name('prices.move');
    });
});
