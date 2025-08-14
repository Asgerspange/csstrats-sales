<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer as CustomerModel;
use App\Models\Subscription as SubscriptionModel;
use App\Models\invoices as InvoicesModel;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\SubscriptionItem;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\Charge;
use Illuminate\Support\Carbon;


class fetchStripeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-stripe-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Log::info('Fetching Stripe data...');
        Stripe::setApiKey(config('services.stripe.secret'));
        cache()->flush();
        $this->getCustomers();
        $this->getSubscriptions();
        $this->getInvoices();
        $this->getDashboardData();
    }

    private function getCustomers()
    {
        $allCustomers = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }
            $response = Customer::all($params);
            foreach ($response->data as $customer) {
                $allCustomers[] = $customer;
            }
            $hasMore = $response->has_more;
            if ($hasMore && count($response->data) > 0) {
                $startingAfter = end($response->data)->id;
            }
        }

        $this->info('Total customers fetched: ' . count($allCustomers));
        $this->info('Inserting customers into the database...');

        foreach ($allCustomers as $customer) {
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
                $needsUpdate = false;
                foreach ($data as $key => $value) {
                    if ($existing->$key != $value) {
                        $needsUpdate = true;
                        break;
                    }
                }

                if ($needsUpdate) {
                    $existing->update($data);
                }
            } else {
                CustomerModel::create(array_merge(['cus_id' => $customer->id], $data));
            }
        }

        $this->info('Customers inserted successfully.');
    }

    private function getSubscriptions()
    {
        $allSubscriptions = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }
            $response = Subscription::all($params);
            foreach ($response->data as $subscription) {
                $allSubscriptions[] = $subscription;
            }
            $hasMore = $response->has_more;
            if ($hasMore && count($response->data) > 0) {
                $startingAfter = end($response->data)->id;
            }
        }

        $this->info('Total subscriptions fetched: ' . count($allSubscriptions));
        $this->info('Inserting subscriptions into the database...');

        foreach ($allSubscriptions as $subscription) {
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
                $needsUpdate = false;
                foreach ($data as $key => $value) {
                    if ($existing->$key != $value) {
                        $needsUpdate = true;
                        break;
                    }
                }

                if ($needsUpdate) {
                    $existing->update($data);
                }
            } else {
                SubscriptionModel::create(array_merge(['sub_id' => $subscription->id], $data));
            }
        }

        $this->info('Subscriptions inserted successfully.');
    }

    private function getInvoices()
    {
        $allInvoices = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }
            $response = \Stripe\Invoice::all($params);
            foreach ($response->data as $invoice) {
                $allInvoices[] = $invoice;
            }
            $hasMore = $response->has_more;
            if ($hasMore && count($response->data) > 0) {
                $startingAfter = end($response->data)->id;
            }
        }

        $this->info('Total invoices fetched: ' . count($allInvoices));
        $this->info('Inserting invoices into the database...');

        foreach ($allInvoices as $invoice) {
            $existing = InvoicesModel::where('invoice_id', $invoice->id)->first();

            $data = [
                'billing_reason' => $invoice->billing_reason,
                'collection_method' => $invoice->collection_method,
                'currency' => $invoice->currency,
                'customer' => $invoice->customer,
                'discounts' => $invoice->discounts,
                'invoice_pdf' => $invoice->invoice_pdf,
                'data' => $invoice->lines['data'],
                'sub_id' => $invoice->parent['subscription_details']['subscription'] ?? null,
                'subtotal' => $invoice->subtotal,
                'subtotal_excluding_tax' => $invoice->subtotal_excluding_tax,
                'status_transitions' => $invoice->status_transitions,
                'created' => $invoice->created
            ];

            if ($existing) {
                $needsUpdate = false;
                foreach ($data as $key => $value) {
                    if ($existing->$key != $value) {
                        $needsUpdate = true;
                        break;
                    }
                }

                if ($needsUpdate) {
                    $existing->update($data);
                }
            } else {
                InvoicesModel::create(array_merge(['invoice_id' => $invoice->id], $data));
            }
        }

        $this->info('Invoices inserted successfully.');
    }

    private function getDashboardData()
    {
        $now = Carbon::now();
        cache()->rememberForever('dashboard_data', function () use ($now) {
            $startOfThisMonth = $now->copy()->startOfMonth();
            $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
            $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

            $chargesThisMonth = Charge::all([
            'created' => ['gte' => $startOfThisMonth->timestamp, 'lte' => $now->timestamp],
            'limit' => 100,
            ]);

            $chargesLastMonth = Charge::all([
            'created' => ['gte' => $startOfLastMonth->timestamp, 'lte' => $endOfLastMonth->timestamp],
            'limit' => 100,
            ]);

            // Calculate revenue for this month and convert to desired currency (e.g., DKK)
            $revenueThisMonthRaw = collect($chargesThisMonth->data)->where('paid', true)->sum('amount') / 100;
            $currency = 'dkk';
            if (!empty($chargesThisMonth->data)) {
                $firstCharge = $chargesThisMonth->data[0];
                $fromCurrency = strtoupper($firstCharge->currency);
            } else {
                $fromCurrency = 'USD';
            }
            $revenueThisMonth = $this->convertCurrency($revenueThisMonthRaw, $fromCurrency, strtoupper($currency));

            $revenueLastMonthRaw = collect($chargesLastMonth->data)->where('paid', true)->sum('amount') / 100;
            if (!empty($chargesLastMonth->data)) {
                $firstChargeLast = $chargesLastMonth->data[0];
                $fromCurrencyLast = strtoupper($firstChargeLast->currency);
            } else {
                $fromCurrencyLast = 'USD';
            }
            $revenueLastMonth = $this->convertCurrency($revenueLastMonthRaw, $fromCurrencyLast, strtoupper($currency));

            $subscriptionsThisMonth = Subscription::all([
            'created' => ['gte' => $startOfThisMonth->timestamp, 'lte' => $now->timestamp],
            'limit' => 100,
            ]);

            $subscriptionsLastMonth = Subscription::all([
            'created' => ['gte' => $startOfLastMonth->timestamp, 'lte' => $endOfLastMonth->timestamp],
            'limit' => 100,
            ]);

            $subscriptionsCountThisMonth = count($subscriptionsThisMonth->data);
            $subscriptionsCountLastMonth = count($subscriptionsLastMonth->data);

            $salesThisMonth = collect($chargesThisMonth->data)->where('paid', true)->count();
            $salesLastMonth = collect($chargesLastMonth->data)->where('paid', true)->count();

            $monthlyRevenue = [];
            for ($i = 1; $i <= 12; $i++) {
                $start = Carbon::create($now->year, $i, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();

                $monthlyCharges = Charge::all([
                    'created' => ['gte' => $start->timestamp, 'lte' => $end->timestamp],
                    'limit' => 100,
                ]);

                $total = collect($monthlyCharges->data)
                    ->where('paid', true)
                    ->sum('amount') / 100;

                // Convert to desired currency, e.g., DKK
                $currency = 'dkk';
                if (!empty($monthlyCharges->data)) {
                    $firstCharge = $monthlyCharges->data[0];
                    $fromCurrency = strtoupper($firstCharge->currency);
                } else {
                    $fromCurrency = 'USD';
                }
                $convertedTotal = $this->convertCurrency($total, $fromCurrency, strtoupper($currency));

                $monthlyRevenue[] = [
                    'month' => $start->format('F'),
                    'revenue' => round($convertedTotal !== false ? $convertedTotal : $total, 2),
                    'currency' => strtoupper($currency),
                ];
            }

            $recentSales = collect($chargesThisMonth->data)
                ->where('paid', true)
                ->sortByDesc('created')
                ->take(10)
                ->map(function ($charge) {
                    return [
                    'id' => $charge->id,
                    'amount' => $charge->amount / 100, strtoupper($charge->currency),
                    'created_at' => Carbon::createFromTimestamp($charge->created)->toDateTimeString(),
                    'customer' => $charge->billing_details['name'] ?? 'Unknown',
                    'currency' => strtoupper($charge->currency),
                    'description' => $charge->description ?? 'No description',
                    ];
                })
            ->values();

            return [
                'revenueThisMonth' => $revenueThisMonth,
                'revenueLastMonth' => $revenueLastMonth,
                'subscriptionsCountThisMonth' => $subscriptionsCountThisMonth,
                'subscriptionsCountLastMonth' => $subscriptionsCountLastMonth,
                'salesThisMonth' => $salesThisMonth,
                'salesLastMonth' => $salesLastMonth,
                'monthlyRevenue' => $monthlyRevenue,
                'recentSales' => $recentSales,
            ];
        });
    }

    private function convertCurrency($amount, $from, $to = 'DKK') {
        $path = storage_path('app/private/currency_rates.json');

        if (!file_exists($path)) {
            return false;
        }

        $rates = json_decode(file_get_contents($path), true);

        if (!isset($rates[$from])) {
            return false;
        }

        return $amount * $rates[$from];
    }
}