<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer as CustomerModel;
use App\Models\Subscription as SubscriptionModel;
use App\Models\Invoices as InvoicesModel;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\Charge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class fetchStripeData extends Command
{
    protected $signature = 'app:fetch-stripe-data {--skip-sync : Skip syncing data to database}';
    protected $description = 'Fetch Stripe data and cache dashboard metrics';

    private $exchangeRates = null;

    public function handle()
    {
        \Log::info('Fetching Stripe data...');
        Stripe::setApiKey(config('services.stripe.secret'));

        cache()->flush();
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
        SubscriptionModel::truncate();

        DB::statement('ALTER TABLE subscriptions AUTO_INCREMENT = 1');
        foreach ($subscriptions as $subscription) {
            SubscriptionModel::create([
                'sub_id' => $subscription->id,
                'object' => $subscription->object,
                'currency' => $subscription->currency,
                'customer' => $subscription->customer,
                'latest_invoice' => $subscription->latest_invoice,
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'items' => $subscription->items,
                'product_id' => $subscription->plan?->product,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'coupon' => $subscription?->discount?->coupon,
                'created' => $subscription->created
            ]);
        }
    }

    private function syncInvoices($invoices)
    {
        foreach ($invoices as $invoice) {
            $existing = InvoicesModel::where('invoice_id', $invoice->id)->first();
            $plan = $invoice->lines['data'][0]->plan ?? null;

            if (isset($plan['product']) && $plan['product'] == 'prod_QN02xiFwIakgsY') {
                if ($existing) {
                    $existing->delete();
                }

                continue;
            }

            $excludedPlanIds = [
                'price_1PWG0uEDh9RJGTHcldLDFUoQ',
                'price_1PglYgEDh9RJGTHcMU02xRxs',
                'price_1PgoePEDh9RJGTHc2MUwwuir',
            ];
            if (isset($plan['id']) && in_array($plan['id'], $excludedPlanIds)) {
                if ($existing) {
                    $existing->delete();
                }
                continue;
            }
            
            $interval = $plan['interval'] ?? null;
            if (!$interval) {
                if ($existing) {
                    $existing->delete();
                }

                continue;
            }

            $intervalCount = $plan['interval_count'] ?? null;
            if ($intervalCount == 6) {
                $interval = 'semi-annually';
            }
            
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
                'payment_interval' => $interval,
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
        set_time_limit(120);
        $this->info('Calculating dashboard metrics...');
        
        $now = Carbon::now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        // Fetch all charges for the year at once
        $invoices = \App\Models\Invoices::with('customerRelation')->where('created', '>=', $startOfYear)->where('created', '<=', $now)->get();

        $allInvoices = collect($invoices);

        // Determine primary currency and target currency
        $primaryCurrency = 'eur';
        $targetCurrency = 'dkk';
        $exchangeRate = $this->getExchangeRate(strtoupper($primaryCurrency), strtoupper($targetCurrency));
        $allInvoices->each(function ($invoice) {
            if ($invoice->discounts) {
                $invoice->subtotal = $invoice->subtotal - ($invoice->data[0]['discount_amounts'][0]['amount'] ?? 0);
                if ($invoice->data[0]['discount_amounts'][0]['discount'] == 'di_1SPQ67EDh9RJGTHcP5csja3Y') {
                    $invoice->subtotal = $invoice->subtotal;
                }
            }
            $invoice->status = $invoice->status_transitions['paid_at'] ? 'succeeded' : 'failed';
        });

        // Calculate monthly metrics
        $paidInvoicesThisMonth = $allInvoices->filter(function ($invoice) use ($startOfThisMonth, $now) {
            $createdAt = Carbon::parse($invoice->created);
            return $createdAt->between($startOfThisMonth, $now) && $invoice->status === 'succeeded';
        });

        $paidInvoicesLastMonth = $allInvoices->filter(function ($invoice) use ($startOfLastMonth, $endOfLastMonth) {
            $createdAt = Carbon::parse($invoice->created);
            return $createdAt->between($startOfLastMonth, $endOfLastMonth) && $invoice->status === 'succeeded';
        });

        $revenueThisMonth = ($paidInvoicesThisMonth->sum('subtotal') / 100) * $exchangeRate;
        $revenueLastMonth = ($paidInvoicesLastMonth->sum('subtotal') / 100) * $exchangeRate;

        // Fetch subscriptions for the period
        $allSubscriptions = collect(\App\Models\Subscription::all());

        $subscriptionsThisYear = $allSubscriptions->filter(function ($subscription) use ($startOfLastMonth, $now) {
            return $subscription->created >= $startOfLastMonth->timestamp && $subscription->created <= $now->timestamp;
        });

        $subscriptionsThisMonth = collect($subscriptionsThisYear)->filter(function ($subscription) use ($startOfThisMonth, $now) {
            return $subscription->created >= $startOfThisMonth->timestamp && $subscription->created <= $now->timestamp;
        });

        $subscriptionsLastMonth = collect($subscriptionsThisYear)->filter(function ($subscription) use ($startOfLastMonth, $endOfLastMonth) {
            return $subscription->created >= $startOfLastMonth->timestamp && $subscription->created <= $endOfLastMonth->timestamp;
        });

        // Calculate monthly revenue breakdown
        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthStart = Carbon::create($now->year, $i, 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthlyTotal = $allInvoices->filter(function ($invoice) use ($monthStart, $monthEnd) {
                $createdAt = Carbon::parse($invoice->created);
                return $createdAt->between($monthStart, $monthEnd);
            })->sum('subtotal') / 100;

            $monthlyRevenue[] = [
                'month' => $monthStart->format('F'),
                'revenue' => round($monthlyTotal * $exchangeRate, 2),
                'currency' => strtoupper($targetCurrency),
            ];
        }

        
        $recentSales = $paidInvoicesThisMonth
            ->sortByDesc('created')
            ->take(10)
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'amount' => ($invoice->subtotal / 100),
                    'created_at' => Carbon::createFromTimestamp($invoice->created)->toDateTimeString(),
                    'customer' => $invoice?->customerRelation?->name ?? 'External Sale',
                    'currency' => $invoice->currency,
                    'description' => $invoice->billing_reason ?? 'No description',
                    'status' => $invoice->status ?? 'unknown'
                ];
            })
            ->values();

        $allPackages = \App\Models\Package::getTrackedPackages();
        $packages = $allPackages->map(function ($package) use ($allSubscriptions) {
            $subscribers = collect($allSubscriptions)->filter(function ($subscription) use ($package) {
                return isset($subscription['items']['data'][0]['price']['id']) && $subscription['items']['data'][0]['price']['id'] === $package->stripe_price_id;
            });

            if ($subscribers->count() === 0) {
                return null;
            }

            return [
                'id' => $package->id,
                'name' => $package->name,
                'price' => $package->price,
                'subscribers' => $subscribers->count(),
                'currency' => $subscribers->pluck('currency')->first(),
                'max_teams' => $package->max_teams,
                'max_members' => $package->max_members,
            ];
        })->filter();

        $dashboardData = [
            'revenueThisMonth' => round($revenueThisMonth, 2),
            'revenueLastMonth' => round($revenueLastMonth, 2),
            'subscriptionsCountThisMonth' => $subscriptionsThisMonth->count(),
            'subscriptionsCountLastMonth' => $subscriptionsLastMonth->count(),
            'salesThisMonth' => $paidInvoicesThisMonth->count(),
            'salesLastMonth' => $paidInvoicesLastMonth->count(),
            'monthlyRevenue' => $monthlyRevenue,
            'recentSales' => $recentSales,
            'lastUpdated' => now()->toISOString(),
            'packages' => $packages
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