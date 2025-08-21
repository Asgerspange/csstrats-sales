<?php
namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;


class DashboardController extends Controller
{
    public function index()
    {
        // Get cached data from the command, fallback to generating if not available
        $dashboardData = cache()->get('dashboard_data');
        
        if (!$dashboardData) {
            // If cache is empty, run the command to populate it
            Artisan::call('app:fetch-stripe-data');
            $dashboardData = cache()->get('dashboard_data', []);
        }
        
        // Calculate percentage changes
        $revenueChange = $this->percentChange(
            $dashboardData['revenueLastMonth'] ?? 0, 
            $dashboardData['revenueThisMonth'] ?? 0
        );
        
        $subscriptionsChange = $this->percentChange(
            $dashboardData['subscriptionsCountLastMonth'] ?? 0, 
            $dashboardData['subscriptionsCountThisMonth'] ?? 0
        );
        
        $salesChange = $this->percentChange(
            $dashboardData['salesLastMonth'] ?? 0, 
            $dashboardData['salesThisMonth'] ?? 0
        );

        return Inertia::render('Dashboard', [
            'revenue' => [
                'total' => $dashboardData['revenueThisMonth'] ?? 0,
                'change' => $revenueChange,
            ],
            'subscriptions' => [
                'count' => $dashboardData['subscriptionsCountThisMonth'] ?? 0,
                'change' => $subscriptionsChange,
            ],
            'sales' => [
                'count' => $dashboardData['salesThisMonth'] ?? 0,
                'change' => $salesChange,
            ],
            'monthlyRevenue' => $dashboardData['monthlyRevenue'] ?? [],
            'recentSales' => $dashboardData['recentSales'] ?? [],
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