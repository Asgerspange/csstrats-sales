<?php

namespace App\Http\Controllers;

use App\Models\{
    Demo,
    BunnyUpload
};
use Inertia\Inertia;

class DemoController extends Controller
{
    public function index()
    {
        $demos = Demo::with('owner')->get();

        return Inertia::render('Admin/Demos/Index', [
            'demos' => $demos,
        ]);
    }

    public function statistics()
    {
        $demos = Demo::get();
        $uploads = BunnyUpload::where('status', '!=', 'processed')->withTrashed()->get();
        $demos = $demos->merge($uploads);
        return Inertia::render('Admin/Demos/Statistics', [
            'demos' => $demos,
        ]);
    }
}
