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

    public function show(Affiliate $affiliate)
    {
        $invoices = $affiliate->invoices()->get();
        $invoices->each(function ($invoice) use ($affiliate) {
            unset($invoice->customer);
            unset($invoice->invoice_id);
            unset($invoice->invoice_pdf);
            unset($invoice->data[0]['plan']);
            $invoice->totalAfterFeesAndVat = $this->calculateTotalAfterFees($invoice->subtotal_excluding_tax, $invoice->data[0]['discount_amounts'][0]['amount'], $affiliate->commission_rate);
        });
        dd($invoices[0]->toArray());

        return Inertia::render('Sales/Affiliates/Show', [
            'affiliate' => $affiliate->load('invoices'),
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
        $additionalStripeFeeEur = 1.8 / 7.47;
        $feesEur = ($subtotalExclTax * $stripeFee + $additionalStripeFeeEur) / 100;
        $vat = 0.2;


        $total = $subtotalExclTax / 100 * (1 - $vat) - $feesEur;

        return round($total, 2);
    }
}
