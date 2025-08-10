<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = cache()->remember('customers.index', 60, function () {
            return Customer::with('user', 'subscriptions')->get();
        });

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function show(Customer $customer)
    {
        return Inertia::render('Customers/Show', [
            'customer' => $customer->load('user', 'subscriptions'),
        ]);
    }
}
