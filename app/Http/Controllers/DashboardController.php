<?php
namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Subscription;

class DashboardController extends Controller
{
    public function index()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $now = Carbon::now();
        
        set_time_limit(100);
        $dashboardData = cache()->remember('dashboard_data', 3600, function () use ($now) {
            $startOfThisMonth = $now->copy()->startOfMonth();
            $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
            $startOfYear = $now->copy()->startOfYear();

            $allCharges = $this->getAllChargesForPeriod($startOfYear->timestamp, $now->timestamp);
            $allSubscriptions = $this->getAllSubscriptionsForPeriod($startOfYear->timestamp, $now->timestamp);

            $paidCharges = collect($allCharges)->where('paid', true);

            $primaryCurrency = $paidCharges->first()->currency ?? 'usd';
            $targetCurrency = 'dkk';
            $exchangeRate = $this->getExchangeRate(strtoupper($primaryCurrency), strtoupper($targetCurrency));

            $chargesThisMonth = $paidCharges->filter(function ($charge) use ($startOfThisMonth, $now) {
                return $charge->created >= $startOfThisMonth->timestamp && $charge->created <= $now->timestamp;
            });

            $chargesLastMonth = $paidCharges->filter(function ($charge) use ($startOfLastMonth, $startOfThisMonth) {
                return $charge->created >= $startOfLastMonth->timestamp && $charge->created < $startOfThisMonth->timestamp;
            });

            $revenueThisMonth = ($chargesThisMonth->sum('amount') / 100) * $exchangeRate;
            $revenueLastMonth = ($chargesLastMonth->sum('amount') / 100) * $exchangeRate;

            $subscriptionsThisMonth = collect($allSubscriptions)->filter(function ($subscription) use ($startOfThisMonth, $now) {
                return $subscription->created >= $startOfThisMonth->timestamp && $subscription->created <= $now->timestamp;
            });

            $subscriptionsLastMonth = collect($allSubscriptions)->filter(function ($subscription) use ($startOfLastMonth, $startOfThisMonth) {
                return $subscription->created >= $startOfLastMonth->timestamp && $subscription->created < $startOfThisMonth->timestamp;
            });

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

            return [
                'revenueThisMonth' => round($revenueThisMonth, 2),
                'revenueLastMonth' => round($revenueLastMonth, 2),
                'subscriptionsCountThisMonth' => $subscriptionsThisMonth->count(),
                'subscriptionsCountLastMonth' => $subscriptionsLastMonth->count(),
                'salesThisMonth' => $chargesThisMonth->count(),
                'salesLastMonth' => $chargesLastMonth->count(),
                'monthlyRevenue' => $monthlyRevenue,
                'recentSales' => $recentSales,
            ];
        });

        $revenueChange = $this->percentChange($dashboardData['revenueLastMonth'], $dashboardData['revenueThisMonth']);
        $subscriptionsChange = $this->percentChange($dashboardData['subscriptionsCountLastMonth'], $dashboardData['subscriptionsCountThisMonth']);
        $salesChange = $this->percentChange($dashboardData['salesLastMonth'], $dashboardData['salesThisMonth']);

        return Inertia::render('Dashboard', [
            'revenue' => [
                'total' => $dashboardData['revenueThisMonth'],
                'change' => $revenueChange,
            ],
            'subscriptions' => [
                'count' => $dashboardData['subscriptionsCountThisMonth'],
                'change' => $subscriptionsChange,
            ],
            'sales' => [
                'count' => $dashboardData['salesThisMonth'],
                'change' => $salesChange,
            ],
            'monthlyRevenue' => $dashboardData['monthlyRevenue'],
            'recentSales' => $dashboardData['recentSales'],
        ]);
    }

    private function getAllChargesForPeriod($startTimestamp, $endTimestamp)
    {
        $allCharges = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = [
                'created' => ['gte' => $startTimestamp, 'lte' => $endTimestamp],
                'limit' => 100,
            ];

            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }

            $charges = Charge::all($params);
            $allCharges = array_merge($allCharges, $charges->data);

            $hasMore = $charges->has_more;
            if ($hasMore && !empty($charges->data)) {
                $startingAfter = end($charges->data)->id;
            }
        }

        return $allCharges;
    }

    private function getAllSubscriptionsForPeriod($startTimestamp, $endTimestamp)
    {
        $allSubscriptions = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = [
                'created' => ['gte' => $startTimestamp, 'lte' => $endTimestamp],
                'limit' => 100,
            ];

            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }

            $subscriptions = Subscription::all($params);
            $allSubscriptions = array_merge($allSubscriptions, $subscriptions->data);

            $hasMore = $subscriptions->has_more;
            if ($hasMore && !empty($subscriptions->data)) {
                $startingAfter = end($subscriptions->data)->id;
            }
        }

        return $allSubscriptions;
    }

    private function getExchangeRate($fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // Cache exchange rates for 30 minutes
        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";
        
        return cache()->remember($cacheKey, 1800, function () use ($fromCurrency, $toCurrency) {
            $rate = $this->convertCurrencyRate($fromCurrency, $toCurrency);
            return $rate !== false ? $rate : 1.0;
        });
    }

    private function convertCurrencyRate($from, $to = 'DKK')
    {
        $path = storage_path('app/private/currency_rates.json');

        if (!file_exists($path)) {
            return false;
        }

        $rates = json_decode(file_get_contents($path), true);

        if (!isset($rates[$from])) {
            return false;
        }

        return $rates[$from];
    }

    private function convertCurrency($amount, $from, $to = 'DKK')
    {
        $rate = $this->getExchangeRate($from, $to);
        return $amount * $rate;
    }

    private function percentChange($old, $new)
    {
        if ($old == 0) {
            return $new == 0 ? 0 : 100;
        }
        return round((($new - $old) / $old) * 100, 2);
    }
}