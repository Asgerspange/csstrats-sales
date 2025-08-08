<?php

use App\Http\Middleware\AuthenticateAdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware([AuthenticateAdminMiddleware::class])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('home');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
