<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration (lead capture) view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Save registration to digital_invoicing.temp_lead, then copy the same row into tbl_lead
     * using the shared RequestID (same pattern as InvoiceModel::save_transaction).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'mobile_no' => ['required', 'string', 'max:15'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $requestId = (string) Str::uuid();

        try {
            DB::connection('landlord')->transaction(function () use ($request, $requestId) {
                DB::connection('landlord')->table('temp_lead')->insert([
                    'BusinessName' => $request->name,
                    'FirstName' => $request->first_name,
                    'LastName' => $request->last_name,
                    'MobileNo' => $request->mobile_no,
                    'Email' => $request->email,
                    'City' => $request->city,
                    'Statusi' => 1,
                    'CreatedBy' => 1,
                    'RequestID' => $requestId,
                    'CreatedOn' => now(),
                ]);

                DB::connection('landlord')->statement(
                    'INSERT INTO tbl_lead (BusinessName, FirstName, LastName, MobileNo, Email, City, Statusi, CreatedBy, RequestID, CreatedOn)
                     SELECT BusinessName, FirstName, LastName, MobileNo, Email, City, Statusi, CreatedBy, RequestID, CreatedOn
                     FROM temp_lead
                     WHERE RequestID = ?',
                    [$requestId]
                );
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', 'Could not save your registration. Please try again later.')
                ->withInput();
        }

        return redirect()
            ->route('register')
            ->with('success', 'Your registration has been received successfully. Our team will contact you shortly.');
    }
}
