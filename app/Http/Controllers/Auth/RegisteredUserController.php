<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Save registration details only to digital_invoicing.tbl_lead (landlord connection).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            DB::connection('landlord')->table('tbl_lead')->insert([
                'BusinessName' => $request->name,
                'FirstName' => $request->first_name,
                'LastName' => $request->last_name,
                'Email' => $request->email,
                'City' => $request->city,
                'Statusi' => 1,
                'CreatedBy' => 1,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['email' => 'Could not save your registration. Please try again later.'])
                ->withInput();
        }

        return redirect()
            ->route('register')
            ->with('success', 'Thank you. Our team will contact you Shortly.');
    }
}
