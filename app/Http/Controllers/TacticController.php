<?php

namespace App\Http\Controllers;

use App\Models\Tactic;
use Inertia\Inertia;

class TacticController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Tactics/Index', [
            'tactics' => Tactic::all()
        ]);
    }
}
