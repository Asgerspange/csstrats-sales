<?php

namespace App\Http\Controllers;

use App\Models\Tactic;
use Inertia\Inertia;
use App\Models\User;
use Carbon\Carbon;

class TacticController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Tactics/Index', [
            'tactic_graph_data' => $this->buildTacticData(),
            'tactics' => Tactic::all()
        ]);
    }

    public function release()
    {
        $unreleasedTactics = Tactic::onlyUnreleased()
            ->orderBy('created_at', 'desc')
            ->get();
        
        $releasedTactics = Tactic::onlyReleased()
            ->orderBy('release_date', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        return inertia('Admin/Tactics/Release', [
            'unreleasedTactics' => $unreleasedTactics,
            'releasedTactics' => $releasedTactics
        ]);
    }

    private function buildTacticData()
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = $endDate->copy()->subMonths(12)->startOfMonth();

        $months = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addMonth()) {
            $months->put($date->format('F Y'), 0);
        }

        $tactics = Tactic::with('user') // Eager load the user relationship
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('created_by') // Group by user ID
            ->map(function ($userTactics) use ($months) {
                $monthlyCounts = $userTactics->groupBy(function ($tactic) {
                    return $tactic->created_at->format('F Y');
                })->map(function ($monthGroup) {
                    return $monthGroup->count();
                });

                return $months->merge($monthlyCounts);
            })
            ->mapWithKeys(function ($monthlyCounts, $userId) {
                $user = User::find($userId); // Fetch the user by ID
                $userName = $user ? $user->name : 'Unknown User';
                return [$userName => $monthlyCounts];
            })
            ->sortKeys(); // Sort by user name


        return $tactics;
    }
}
