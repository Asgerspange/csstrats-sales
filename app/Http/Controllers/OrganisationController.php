<?php

namespace App\Http\Controllers;

use App\Models\{
    Organisation,
    Customer,
    OrganisationContact,
    Contact
};
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganisationController extends Controller
{
    public function index()
    {
        $organisations = cache()->remember('organisations.index', now()->addMinutes(60), function () {
            return Organisation::get();
        });
        return Inertia::render('Organisations/Index', [
            'organisations' => $organisations,
        ]);
    }

    public function show(Organisation $organisation)
    {
        $cacheKey = "organisations.show.{$organisation->id}";

        $organisation = cache()->remember($cacheKey, now()->addMinutes(60), function () use ($organisation) {
            return $organisation->load('customer', 'contacts.customer', 'contacts.contact', 'packages');
        });
        $organisations = cache()->remember('organisations.index', now()->addMinutes(60), function () {
            return Organisation::all();
        });
        $cachedCustomers = cache()->remember('customers.index', now()->addMinutes(60), function () {
            $customers =  Customer::with('user', 'subscriptions')->get();

            return $customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                ];
            })->values()->all();
        });

        return Inertia::render('Organisations/Show', [
            'organisation' => $organisation,
            'customers' => $cachedCustomers,
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

    public function changeCustomer(Request $request, Organisation $organisation)
    {
        $validatedData = $request->validate([
            'cus_id' => 'required|exists:customers,id',
        ]);

        OrganisationContact::updateOrCreate(
            [
            'organisation_id' => $organisation->id,
            'cus_id' => $organisation->cus_id,
            ],
            [
            'cus_id' => $validatedData['cus_id'],
            'is_primary' => false
            ]
        );

        $organisation->cus_id = $validatedData['cus_id'];
        $organisation->save();

        cache()->forget("organisations.show.{$organisation->id}");
        cache()->remember("organisations.show.{$organisation->id}", now()->addMinutes(60), function () use ($organisation) {
            return $organisation->load('customer', 'contacts.customer', 'contacts.contact', 'packages');
        });

        return response()->json([
            'message' => 'Customer changed successfully',
            'organisation' => $organisation,
        ]);
    }

    public function makePrimaryContact(Request $request, Organisation $organisation)
    {
        $validatedData = $request->validate([
            'contact_id' => 'required|exists:organisation_contacts,id',
        ]);

        OrganisationContact::where('organisation_id', $organisation->id)
            ->update(['is_primary' => false]);

        $contact = OrganisationContact::findOrFail($validatedData['contact_id']);
        $contact->is_primary = true;
        $contact->save();

        cache()->forget("organisations.show.{$organisation->id}");
        cache()->remember("organisations.show.{$organisation->id}", now()->addMinutes(60), function () use ($organisation) {
            return $organisation->load('customer', 'contacts.customer', 'contacts.contact', 'packages');
        });

        return response()->json([
            'message' => 'Primary contact updated successfully',
            'organisation' => $organisation,
        ]);
    }

    public function removeContact(Request $request, Organisation $organisation)
    {
        $validatedData = $request->validate([
            'contact_id' => 'required|exists:organisation_contacts,id',
        ]);

        $organisationContact = OrganisationContact::findOrFail($validatedData['contact_id']);

        if ($organisationContact->contact_id) {
            $organisationContact->contact()->delete();
        }

        $organisationContact->delete();

        cache()->forget("contacts.index");
        cache()->remember('contacts.index', now()->addMinutes(60), function () {
            return Contact::with('organisation')->get();
        });
        cache()->forget("organisations.show.{$organisation->id}");
        cache()->remember("organisations.show.{$organisation->id}", now()->addMinutes(60), function () use ($organisation) {
            return $organisation->load('customer', 'contacts.customer', 'contacts.contact', 'packages');
        });

        return response()->json([
            'message' => 'Contact removed successfully',
            'organisation' => $organisation,
        ]);
    }
}
