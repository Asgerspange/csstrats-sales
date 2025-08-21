<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = cache()->remember('users.all', 60, function () {
            return User::with('grantedAccess.grantedBy')->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'has_granted_access' => $user->has_granted_access,
                        'granted_access' => $user->grantedAccess,
                    ];
                });
        });

        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    public function show(User $user)
    {
        $user->load(['grantedAccess.grantedBy']);

        return Inertia::render('Users/Show', [
            'user' => $user
        ]);
    }


    public function grantAccess(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'max_teams' => 'required|integer|min:1',
            'max_members' => 'required|integer|min:1',
            'max_stratbooks' => 'required|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = User::findOrFail($request->input('user_id'));
        $user->grantedAccess()->create([
            'granted_by' => auth()->id(),
            'granted_at' => now(),
            'expires_at' => $request->input('expires_at'),
            'max_teams' => $request->input('max_teams'),
            'max_members' => $request->input('max_members'),
            'max_stratbooks' => $request->input('max_stratbooks'),
        ]);

        return redirect()->route('admin.users.show', $user)->with('success', 'Access granted successfully.');
    }
}
