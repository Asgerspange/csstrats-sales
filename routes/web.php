<?php

use App\Http\Middleware\AuthenticateAdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware([AuthenticateAdminMiddleware::class])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('home');
    Route::get('customers', [App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');

    Route::post('clear-cache', function () {
        cache()->flush();
    })->name('clear-cache');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
