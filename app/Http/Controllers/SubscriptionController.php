<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index()
    {
        return Inertia::render('Sales/Billing/Subscriptions/Index', [
            'subscriptions' => Subscription::with([
                'customerRelation',
                'package' => function($query) {
                    $query->select('id', 'price', 'name', 'prod_id');
                }
            ])->get()
        ]);
    }
}
