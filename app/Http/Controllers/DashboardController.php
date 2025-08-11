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
        // Try to cache dashboard data for 5 minutes to reduce Stripe API calls
        $now = Carbon::now();
        $dashboardData = cache()->remember('dashboard_data', now()->addMinutes(60), function () use ($now) {
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

            $revenueThisMonth = collect($chargesThisMonth->data)->where('paid', true)->sum('amount') / 100;
            $revenueLastMonth = collect($chargesLastMonth->data)->where('paid', true)->sum('amount') / 100;

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

            $monthlyRevenue[] = [
                'month' => $start->format('F'),
                'revenue' => round($total, 2),
            ];
            }

            $recentSales = collect($chargesThisMonth->data)
                ->where('paid', true)
                ->sortByDesc('created')
                ->take(10)
                ->map(function ($charge) {
                    return [
                    'id' => $charge->id,
                    'amount' => $charge->amount / 100,
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

        $revenueThisMonth = $dashboardData['revenueThisMonth'];
        $revenueLastMonth = $dashboardData['revenueLastMonth'];
        $revenueChange = $this->percentChange($revenueLastMonth, $revenueThisMonth);

        $subscriptionsCountThisMonth = $dashboardData['subscriptionsCountThisMonth'];
        $subscriptionsCountLastMonth = $dashboardData['subscriptionsCountLastMonth'];
        $subscriptionsChange = $this->percentChange($subscriptionsCountLastMonth, $subscriptionsCountThisMonth);

        $salesThisMonth = $dashboardData['salesThisMonth'];
        $salesLastMonth = $dashboardData['salesLastMonth'];
        $salesChange = $this->percentChange($salesLastMonth, $salesThisMonth);

        $monthlyRevenue = $dashboardData['monthlyRevenue'];
        $recentSales = $dashboardData['recentSales'];

        return Inertia::render('Dashboard', [
            'revenue' => [
                'total' => $revenueThisMonth,
                'change' => $revenueChange,
            ],
            'subscriptions' => [
                'count' => $subscriptionsCountThisMonth,
                'change' => $subscriptionsChange,
            ],
            'sales' => [
                'count' => $salesThisMonth,
                'change' => $salesChange,
            ],
            'monthlyRevenue' => $monthlyRevenue,
            'recentSales' => $recentSales,
        ]);
    }

    private function percentChange($old, $new)
    {
        if ($old == 0) {
            return $new == 0 ? 0 : 100;
        }
        return round((($new - $old) / $old) * 100, 2);
    }
}
