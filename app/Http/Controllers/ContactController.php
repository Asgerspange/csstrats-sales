<?php

namespace App\Http\Controllers;

use App\Models\{
    Contact,
    Organisation,
    OrganisationContact
};
use Illuminate\Http\Request;
use Inertia\Inertia;


class ContactController extends Controller
{
    public function index()
    {
        $contacts = cache()->remember('contacts.index', now()->addMinutes(60), function () {
            return Contact::with('organisation')->get();
        });

        $organisations = cache()->remember('organisations.index', now()->addMinutes(60), function () {
            return Organisation::all();
        });

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'organisations' => $organisations
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:contacts',
            'phone' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'job' => 'nullable|string|max:255',
            'organisation_id' => 'nullable|exists:organisations,id',
        ]);

        $contact = Contact::create($validated);
        cache()->forget('contacts.index');
        
        cache()->remember("contacts.index", now()->addMinutes(60), function () {
            return Contact::with('organisation')->get();
        });

        if ($contact->organisation_id) {
            $cacheKey = "organisations.show.{$contact->organisation_id}";
            OrganisationContact::updateOrCreate(
                ['organisation_id' => $contact->organisation_id, 'contact_id' => $contact->id]
            );

            cache()->forget($cacheKey);
            cache()->remember($cacheKey, now()->addMinutes(60), function () use ($contact) {
                return $contact->organisation->load('customer', 'contacts.customer', 'contacts.contact');
            });
        }

        return response()->json(['contact' => $contact], 201);
    }
}
