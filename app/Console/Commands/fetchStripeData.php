<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer as CustomerModel;
use App\Models\Subscription as SubscriptionModel;
use App\Models\invoices as InvoicesModel;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\Charge;
use Illuminate\Support\Carbon;

class fetchStripeData extends Command
{
    protected $signature = 'app:fetch-stripe-data {--skip-sync : Skip syncing data to database}';
    protected $description = 'Fetch Stripe data and cache dashboard metrics';

    private $exchangeRates = null;

    public function handle()
    {
        \Log::info('Fetching Stripe data...');
        Stripe::setApiKey(config('services.stripe.secret'));
        
        // Load exchange rates once
        $this->loadExchangeRates();
        
        if (!$this->option('skip-sync')) {
            $this->getCustomers();
            $this->getSubscriptions();
            $this->getInvoices();
        }
        
        $this->getDashboardData();
        $this->info('Dashboard data cached successfully.');
    }

    private function loadExchangeRates()
    {
        $path = storage_path('app/private/currency_rates.json');
        
        if (file_exists($path)) {
            $this->exchangeRates = json_decode(file_get_contents($path), true);
        } else {
            $this->warn('Exchange rates file not found. Using 1:1 conversion.');
            $this->exchangeRates = [];
        }
    }

    private function getCustomers()
    {
        $this->info('Fetching customers...');
        $allCustomers = $this->fetchAllStripeData(Customer::class);
        $this->info('Total customers fetched: ' . count($allCustomers));
        
        $this->syncCustomers($allCustomers);
        $this->info('Customers synced successfully.');
    }

    private function getSubscriptions()
    {
        $this->info('Fetching subscriptions...');
        $allSubscriptions = $this->fetchAllStripeData(Subscription::class);
        $this->info('Total subscriptions fetched: ' . count($allSubscriptions));
        
        $this->syncSubscriptions($allSubscriptions);
        $this->info('Subscriptions synced successfully.');
    }

    private function getInvoices()
    {
        $this->info('Fetching invoices...');
        $allInvoices = $this->fetchAllStripeData(Invoice::class);
        $this->info('Total invoices fetched: ' . count($allInvoices));
        
        $this->syncInvoices($allInvoices);
        $this->info('Invoices synced successfully.');
    }

    private function fetchAllStripeData($stripeClass, array $additionalParams = [])
    {
        $allData = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = array_merge(['limit' => 100], $additionalParams);
            
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }
            
            $response = $stripeClass::all($params);
            $allData = array_merge($allData, $response->data);
            
            $hasMore = $response->has_more;
            if ($hasMore && !empty($response->data)) {
                $startingAfter = end($response->data)->id;
            }
        }

        return $allData;
    }

    private function syncCustomers($customers)
    {
        foreach ($customers as $customer) {
            $existing = CustomerModel::where('cus_id', $customer->id)->first();

            $data = [
                'object' => $customer->object,
                'address' => $customer->address,
                'balance' => $customer->balance,
                'created' => $customer->created,
                'currency' => $customer->currency,
                'description' => $customer->description,
                'email' => $customer->email,
                'invoice_prefix' => $customer->invoice_prefix,
                'invoice_settings' => $customer->invoice_settings,
                'metadata' => $customer->metadata,
                'name' => $customer->name
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                CustomerModel::create(array_merge(['cus_id' => $customer->id], $data));
            }
        }
    }

    private function syncSubscriptions($subscriptions)
    {
        foreach ($subscriptions as $subscription) {
            $existing = SubscriptionModel::where('sub_id', $subscription->id)->first();

            $data = [
                'object' => $subscription->object,
                'currency' => $subscription->currency,
                'customer' => $subscription->customer,
                'latest_invoice' => $subscription->latest_invoice,
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'coupon' => $subscription?->discount?->coupon,
                'created' => $subscription->created
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                SubscriptionModel::create(array_merge(['sub_id' => $subscription->id], $data));
            }
        }
    }

    private function syncInvoices($invoices)
    {
        foreach ($invoices as $invoice) {
            $existing = InvoicesModel::where('invoice_id', $invoice->id)->first();

            $data = [
                'billing_reason' => $invoice->billing_reason,
                'collection_method' => $invoice->collection_method,
                'currency' => $invoice->currency,
                'customer' => $invoice->customer,
                'discounts' => $invoice->discounts,
                'invoice_pdf' => $invoice->invoice_pdf,
                'data' => $invoice->lines['data'],
                'sub_id' => $invoice->subscription ?? null,
                'subtotal' => $invoice->subtotal,
                'subtotal_excluding_tax' => $invoice->subtotal_excluding_tax,
                'status_transitions' => $invoice->status_transitions,
                'created' => $invoice->created
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                InvoicesModel::create(array_merge(['invoice_id' => $invoice->id], $data));
            }
        }
    }

    private function getDashboardData()
    {
        $this->info('Calculating dashboard metrics...');
        
        $now = Carbon::now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        // Fetch all charges for the year at once
        $allCharges = $this->fetchAllStripeData(Charge::class, [
            'created' => ['gte' => $startOfYear->timestamp, 'lte' => $now->timestamp]
        ]);

        // Filter paid charges
        $paidCharges = collect($allCharges)->where('paid', true);

        // Determine primary currency and target currency
        $primaryCurrency = $paidCharges->first()->currency ?? 'usd';
        $targetCurrency = 'dkk';
        $exchangeRate = $this->getExchangeRate(strtoupper($primaryCurrency), strtoupper($targetCurrency));

        // Calculate monthly metrics
        $chargesThisMonth = $paidCharges->filter(function ($charge) use ($startOfThisMonth, $now) {
            return $charge->created >= $startOfThisMonth->timestamp && $charge->created <= $now->timestamp;
        });

        $chargesLastMonth = $paidCharges->filter(function ($charge) use ($startOfLastMonth, $endOfLastMonth) {
            return $charge->created >= $startOfLastMonth->timestamp && $charge->created <= $endOfLastMonth->timestamp;
        });

        $revenueThisMonth = ($chargesThisMonth->sum('amount') / 100) * $exchangeRate;
        $revenueLastMonth = ($chargesLastMonth->sum('amount') / 100) * $exchangeRate;

        // Fetch subscriptions for the period
        $allSubscriptions = $this->fetchAllStripeData(Subscription::class, [
            'created' => ['gte' => $startOfLastMonth->timestamp, 'lte' => $now->timestamp]
        ]);

        $subscriptionsThisMonth = collect($allSubscriptions)->filter(function ($subscription) use ($startOfThisMonth, $now) {
            return $subscription->created >= $startOfThisMonth->timestamp && $subscription->created <= $now->timestamp;
        });

        $subscriptionsLastMonth = collect($allSubscriptions)->filter(function ($subscription) use ($startOfLastMonth, $endOfLastMonth) {
            return $subscription->created >= $startOfLastMonth->timestamp && $subscription->created <= $endOfLastMonth->timestamp;
        });

        // Calculate monthly revenue breakdown
        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthStart = Carbon::create($now->year, $i, 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthlyTotal = $paidCharges->filter(function ($charge) use ($monthStart, $monthEnd) {
                return $charge->created >= $monthStart->timestamp && $charge->created <= $monthEnd->timestamp;
            })->sum('amount') / 100;

            $monthlyRevenue[] = [
                'month' => $monthStart->format('F'),
                'revenue' => round($monthlyTotal * $exchangeRate, 2),
                'currency' => strtoupper($targetCurrency),
            ];
        }

        // Get recent sales
        $recentSales = $chargesThisMonth
            ->sortByDesc('created')
            ->take(10)
            ->map(function ($charge) use ($targetCurrency, $exchangeRate) {
                return [
                    'id' => $charge->id,
                    'amount' => round(($charge->amount / 100) * $exchangeRate, 2),
                    'created_at' => Carbon::createFromTimestamp($charge->created)->toDateTimeString(),
                    'customer' => $charge->billing_details->name ?? 'Unknown',
                    'currency' => strtoupper($targetCurrency),
                    'description' => $charge->description ?? 'No description',
                ];
            })
            ->values();

        $dashboardData = [
            'revenueThisMonth' => round($revenueThisMonth, 2),
            'revenueLastMonth' => round($revenueLastMonth, 2),
            'subscriptionsCountThisMonth' => $subscriptionsThisMonth->count(),
            'subscriptionsCountLastMonth' => $subscriptionsLastMonth->count(),
            'salesThisMonth' => $chargesThisMonth->count(),
            'salesLastMonth' => $chargesLastMonth->count(),
            'monthlyRevenue' => $monthlyRevenue,
            'recentSales' => $recentSales,
            'lastUpdated' => now()->toISOString(),
        ];

        // Cache the data
        cache()->put('dashboard_data', $dashboardData, now()->addHours(1));
        
        $this->info('Dashboard data calculated and cached.');
    }

    private function getExchangeRate($fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        if (!$this->exchangeRates || !isset($this->exchangeRates[$fromCurrency])) {
            return 1.0;
        }

        return $this->exchangeRates[$fromCurrency];
    }

    private function convertCurrency($amount, $from, $to = 'DKK')
    {
        $rate = $this->getExchangeRate($from, $to);
        return $amount * $rate;
    }
}