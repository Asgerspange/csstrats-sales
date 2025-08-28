<?php

use App\Http\Middleware\AuthenticateAdminMiddleware;
use App\Mail\MassMail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware([AuthenticateAdminMiddleware::class])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('home');
    Route::get('mails', [App\Http\Controllers\MailController::class, 'index'])->name('mails.index');
    Route::get('/mails/create', [App\Http\Controllers\MailController::class, 'create'])->name('mails.create');
    Route::post('/mails', [App\Http\Controllers\MailController::class, 'store'])->name('mails.store');
    Route::get('/mails/{id}', [App\Http\Controllers\MailController::class, 'show'])->name('mails.show');

    Route::prefix('sales')->group(function () {
        Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('sales.index');

        Route::get('customers', [App\Http\Controllers\CustomerController::class, 'index'])->name('sales.customers.index');
        Route::get('customers/{customer}', [App\Http\Controllers\CustomerController::class, 'show'])->name('sales.customers.show');
        Route::get('packages', [App\Http\Controllers\PackageController::class, 'index'])->name('sales.packages.index');
        Route::get('packages/{package}', [App\Http\Controllers\PackageController::class, 'show'])->name('sales.packages.show');
        Route::post('packages', [App\Http\Controllers\PackageController::class, 'store'])->name('sales.packages.store');
        Route::delete('packages/{package}', [App\Http\Controllers\PackageController::class, 'destroy'])->name('sales.packages.destroy');
        Route::get('organisations', [App\Http\Controllers\OrganisationController::class, 'index'])->name('sales.organisations.index');
        Route::get('organisations/{organisation}', [App\Http\Controllers\OrganisationController::class, 'show'])->name('sales.organisations.show');
        Route::post('organisations', [App\Http\Controllers\OrganisationController::class, 'store'])->name('sales.organisations.store');
        Route::delete('organisations/{organisation}', [App\Http\Controllers\OrganisationController::class, 'destroy'])->name('sales.organisations.destroy');

        Route::post('organisations/{organisation}/change-customer', [App\Http\Controllers\OrganisationController::class, 'changeCustomer'])->name('sales.organisations.change-customer');
        Route::post('organisations/{organisation}/make-primary-contact', [App\Http\Controllers\OrganisationController::class, 'makePrimaryContact'])->name('sales.organisations.make-primary-contact');
        Route::post('organisations/{organisation}/remove-contact', [App\Http\Controllers\OrganisationController::class, 'removeContact'])->name('sales.organisations.remove-contact');
        Route::post('organisations/{organisation}/update-notes', [App\Http\Controllers\OrganisationController::class, 'updateNotes'])->name('sales.organisations.update-notes');

        Route::prefix('billing')->group(function () {
            Route::get('', [App\Http\Controllers\BillingController::class, 'index'])->name('sales.billing.index');
            Route::get('calendar', [App\Http\Controllers\BillingController::class, 'calendar'])->name('sales.billing.calendar');
            Route::get('date/{date}', [App\Http\Controllers\BillingController::class, 'dayAnalysis'])->name('sales.billing.day');
            Route::get('subscriptions', [App\Http\Controllers\SubscriptionController::class, 'index'])->name('sales.billing.subscriptions.index');
        });

        Route::get('contacts', [App\Http\Controllers\ContactController::class, 'index'])->name('sales.contacts.index');
        Route::post('contacts', [App\Http\Controllers\ContactController::class, 'store'])->name('sales.contacts.store');
    });

    Route::prefix('admin')->group(function () {
        Route::get('users', [App\Http\Controllers\UserController::class, 'index'])->name('admin.users.index');
        Route::get('users/{user}', [App\Http\Controllers\UserController::class, 'show'])->name('admin.users.show');
        Route::post('grant-access', [App\Http\Controllers\UserController::class, 'grantAccess'])->name('admin.grant-access');
    });
    
    // Route::get('massSendMail', [App\Http\Controllers\MailController::class, 'sendMassMail'])->name('massmail.index');

    Route::get('/preview-mail', function () {
        $recipient = ['name' => 'Kunde 1', 'email' => 'test@example.com'];
        return new MassMail($recipient['name'], $recipient['email']);
    });
    Route::post('clear-cache', function () {
        \Log::info('Cache cleared by admin user');
        cache()->flush();
    })->name('clear-cache');

    //Admin routes

});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
