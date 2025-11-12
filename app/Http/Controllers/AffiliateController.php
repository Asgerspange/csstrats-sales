<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Sales/Affiliates/Index', [
            'affiliates' => Affiliate::all(),
        ]);
    }

    public function store(Request $request)
    {
        // Validate and store the affiliate data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:affiliates,email',
            'bank_account' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'promocode.code' => 'required|string|max:50',
            'promocode.discount_percentage' => 'required|numeric|min:0|max:100',
            'access_token' => 'required|string|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'min_payout_amount' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive',
        ]);

        $validated['promocode'] = $validated['promocode']['code'];

        // Create the affiliate (assuming an Affiliate model exists)
        Affiliate::create($validated);

        return redirect()->route('sales.affiliates.index')->with('success', 'Affiliate created successfully.');
    }

    public function destroy(Affiliate $affiliate)
    {
        $affiliate->delete();

        return redirect()->route('sales.affiliates.index')->with('success', 'Affiliate deleted successfully.');
    }
}
