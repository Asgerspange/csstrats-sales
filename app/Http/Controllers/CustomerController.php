<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $firstCustomer = Customer::with('user')->first();

        $customers = cache()->remember('customers.index', 60, function () {
            return Customer::with('user', 'subscriptions')->get();
        });

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }
}
