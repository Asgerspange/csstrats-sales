<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganisationController extends Controller
{
    public function index()
    {
        return Inertia::render('Organisations/Index', [
            'organisations' => fn () => Organisation::get(),
        ]);
    }

    public function show(Organisation $organisation)
    {
        return Inertia::render('Organisations/Show', [
            'organisation' => $organisation,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'cvr' => 'required|string|max:255',
            'country' => 'required|string|max:2',
            'address' => 'required|string|max:255',
            'zip' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        if (Organisation::where('cvr', $validatedData['cvr'])->exists()) {
            return response()->json(['error' => 'CVR already exists'], 409);
        }

        $organisation = Organisation::create($validatedData);

        return response()->json([
            'message' => 'Organisation created successfully',
            'organisation' => $organisation
        ], 201);
    }

    public function destroy(Organisation $organisation)
    {
        $organisation->delete();

        return response()->json([
            'message' => 'Organisation deleted successfully',
        ]);
    }
}
