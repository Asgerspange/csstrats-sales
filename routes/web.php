<?php

use App\Http\Middleware\AuthenticateAdminMiddleware;
use App\Mail\MassMail;
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
    Route::post('organisations/{organisation}/remove-contact', [App\Http\Controllers\OrganisationController::class, 'removeContact'])->name('organisations.remove-contact');
    Route::post('organisations/{organisation}/update-notes', [App\Http\Controllers\OrganisationController::class, 'updateNotes'])->name('organisations.update-notes');

    Route::get('contacts', [App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
    Route::post('contacts', [App\Http\Controllers\ContactController::class, 'store'])->name('contacts.store');

    Route::get('packages', [App\Http\Controllers\PackageController::class, 'index'])->name('packages.index');
    Route::get('packages/{package}', [App\Http\Controllers\PackageController::class, 'show'])->name('packages.show');
    Route::post('packages', [App\Http\Controllers\PackageController::class, 'store'])->name('packages.store');
    Route::delete('packages/{package}', [App\Http\Controllers\PackageController::class, 'destroy'])->name('packages.destroy');

    Route::get('mails', [App\Http\Controllers\MailController::class, 'index'])->name('mails.index');
    Route::get('/mails/create', [App\Http\Controllers\MailController::class, 'create'])->name('mails.create');
    Route::post('/mails', [App\Http\Controllers\MailController::class, 'store'])->name('mails.store');
    Route::get('/mails/{id}', [App\Http\Controllers\MailController::class, 'show'])->name('mails.show');
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
    Route::get('admin/users', [App\Http\Controllers\UserController::class, 'index'])->name('admin.users.index');
    Route::get('admin/users/{user}', [App\Http\Controllers\UserController::class, 'show'])->name('admin.users.show');

    Route::post('admin/grant-access', [App\Http\Controllers\UserController::class, 'grantAccess'])->name('admin.grant-access');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
