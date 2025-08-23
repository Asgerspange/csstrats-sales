<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = cache()->remember('customers.index', now()->addMinutes(60), function () {
            return Customer::with('user', 'subscriptions')->get();
        });

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function show(Customer $customer)
    {
        $customer = cache()->remember("customers.show.{$customer->cus_id}", now()->addMinutes(60), function () use ($customer) {
            return $customer->load('user', 'subscriptions', 'invoices', 'organisation');
        });

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }
}
