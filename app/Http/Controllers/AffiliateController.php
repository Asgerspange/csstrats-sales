<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\Invoices;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AffiliateController extends Controller
{
    private $exchangeRates = [];
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

    public function show(Affiliate $affiliate)
    {
        $this->loadExchangeRates();

        $invoices = Invoices::get();
        $couponInvoices = $invoices->where('coupon', $affiliate->coupon);
        $allCustomers = $couponInvoices->pluck('customer')->unique();
        
        $totalInvoices = $couponInvoices;
        foreach ($invoices as $invoice) {
            if ($invoice->customer && $allCustomers->contains($invoice->customer)) {
                $totalInvoices->push($invoice);
            }
        }

        $totalInvoices = $totalInvoices->map(function ($invoice) use ($affiliate) {
            $discountAmount = isset($invoice->data[0]['discount_amounts'][0]['amount']) ? $invoice->data[0]['discount_amounts'][0]['amount'] : null;
            return [
                'id' => $invoice->id,
                'totalAfterFeesAndVat' => $this->calculateTotalAfterFees($invoice->subtotal_excluding_tax, $discountAmount, $affiliate->commission_rate),
                'subtotal_excluding_tax' => $invoice->subtotal_excluding_tax * ($this->exchangeRates['EUR']),
                'coupon' => $invoice->coupon,
                'created_at' => $invoice->created_at,
            ];
        })->toArray();

        return Inertia::render('Sales/Affiliates/Show', [
            'affiliate' => $affiliate,
            'invoices' => $totalInvoices,
        ]);
    }

    public function destroy(Affiliate $affiliate)
    {
        $affiliate->delete();

        return redirect()->route('sales.affiliates.index')->with('success', 'Affiliate deleted successfully.');
    }
    
    private function calculateTotalAfterFees($subtotalExclTax, $discountAmount, $commissionRate)
    {
        if ($discountAmount == $subtotalExclTax) {
            return 0.00;
        }

        $stripeFee = 0.04;
        $additionalStripeFeeEur = 1.8 / $this->exchangeRates['EUR'];
        $feesEur = ($subtotalExclTax * $stripeFee + $additionalStripeFeeEur) / 100;
        $vat = 0.2;


        $total = $subtotalExclTax * (1 - $vat) - $feesEur;
        $totalDkk = $total * ($this->exchangeRates['EUR']);

        return round($totalDkk, 2);
    }

    private function loadExchangeRates()
    {
        $path = storage_path('app/private/currency_rates.json');
        
        if (file_exists($path)) {
            $this->exchangeRates = json_decode(file_get_contents($path), true);
        } else {
            $this->exchangeRates = [];
        }
    }
}
