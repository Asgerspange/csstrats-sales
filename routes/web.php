<?php

use App\Http\Middleware\AuthenticateAdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware([AuthenticateAdminMiddleware::class])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('home');

    Route::get('customers', [App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');

    Route::get('organisations', [App\Http\Controllers\OrganisationController::class, 'index'])->name('organisations.index');
    Route::get('organisations/{organisation}', [App\Http\Controllers\OrganisationController::class, 'show'])->name('organisations.show');
    Route::post('organisations', [App\Http\Controllers\OrganisationController::class, 'store'])->name('organisations.store');
    Route::delete('organisations/{organisation}', [App\Http\Controllers\OrganisationController::class, 'destroy'])->name('organisations.destroy');

    Route::post('organisations/{organisation}/change-customer', [App\Http\Controllers\OrganisationController::class, 'changeCustomer'])->name('organisations.change-customer');
    Route::post('organisations/{organisation}/make-primary-contact', [App\Http\Controllers\OrganisationController::class, 'makePrimaryContact'])->name('organisations.make-primary-contact');

    Route::post('clear-cache', function () {
        \Log::info('Cache cleared by admin user');
        cache()->flush();
    })->name('clear-cache');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
