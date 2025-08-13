<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = cache()->remember('packages.index', now()->addMinutes(60), function () {
            return Package::with('organisation')->where('is_custom', 1)->get();
        });
        $organisations = cache()->remember('organisations.index', now()->addMinutes(60), function () {
            return \App\Models\Organisation::all();
        });
        return Inertia::render('Packages/Index', [
            'packages' => $packages,
            'organisations' => $organisations,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'monthly_price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'max_members' => 'required|integer|min:1',
            'max_stratbooks' => 'required|integer|min:1',
            'max_teams' => 'required|integer|min:1',
            'organisation_id' => 'required|exists:organisations,id',
        ]);

        $stripeProduct = $this->createStripeProduct($data);

        $package = Package::create(array_merge($data, [
            'stripe_price_id' => $stripeProduct['stripe_price_id'],
            'prod_id' => $stripeProduct['stripe_product_id'],
            'hide' => 1,
            'is_custom' => true,
        ]));

        cache()->forget('packages.index');
        cache()->remember('packages.index', now()->addMinutes(60), function () {
            return Package::with('organisation')->where('is_custom', 1)->get();
        });

        return response()->json([
            'message' => 'Package created successfully',
            'package' => $package
        ], 201);
    }

    private function createStripeProduct(array $data)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $product = \Stripe\Product::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'metadata' => [
                'organisation_id' => $data['organisation_id'],
            ],
        ]);

        $yearly = \Stripe\Price::create([
            'unit_amount' => $data['price'] * 100,
            'currency' => 'eur',
            'recurring' => [
                'interval' => 'year',
            ],
            'product' => $product->id,
        ]);

        $monthly = \Stripe\Price::create([
            'unit_amount' => $data['monthly_price'] * 100,
            'currency' => 'eur',
            'recurring' => [
                'interval' => 'month',
            ],
            'product' => $product->id,
        ]);
        
        return [
            'stripe_product_id' => $product->id,
            'stripe_price_id' => $yearly->id,
            'stripe_monthly_price_id' => $monthly->id,
        ];
    }
}
