<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index()
    {
        $customers = Customer::active()
            ->orderBy('CreatedOn', 'desc')
            ->get();
        
        return view('pages.dashboards.customers-list', compact('customers'));
    }
    
    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return view('pages.dashboards.customers-create');
    }
    
    /**
     * Store a newly created customer - CALLS MODEL save_transaction()
     */
   public function save_transaction(Request $request)
    {
        // 1. Handle "Same as Billing" Logic
        if ($request->has('SameAsBilling') && $request->SameAsBilling == '1') {
            $request->merge([
                'BillingAddress' => $request->Address,
                'BillingCity' => $request->City,
                'BillingCountry' => $request->Country,
                'BillingCounty' => $request->County,
                'BillingPostalCode' => $request->PostalCode,
            ]);
        }
        
        // 2. Validate the Request
        $validated = $request->validate([
            'Title' => 'required|string|max:50',
            'FirstName' => 'required|string|max:100',
            'LastName' => 'required|string|max:100',
            'BusinessName' => 'required|string|max:200',
            'DisplayName' => 'nullable|string|max:200',
            'Email' => 'required|email|max:500',
            'Mobile' => 'required|string|max:100',
            'Phone' => 'nullable|string|max:100',
            'Website' => 'nullable|url|max:200',
            'Address' => 'required|string|max:500',
            'City' => 'required|string|max:100',
            'Country' => 'required|string|max:100',
            'County' => 'required|string|max:100',
            'PostalCode' => 'required|string|max:20',
            'BillingAddress' => 'required|string|max:500',
            'BillingCity' => 'required|string|max:100',
            'BillingCountry' => 'required|string|max:100',
            'BillingCounty' => 'required|string|max:100',
            'BillingPostalCode' => 'required|string|max:20',
            'SameAsBilling' => 'nullable|boolean',
            'NTN' => 'required|string|max:20',
            'SalesTaxNumber' => 'nullable|string|max:50',
            'IsFiler' => 'nullable|boolean',
            'CNIC' => 'required|string|max:50',
            'CreditLimit' => 'required|numeric|min:0',
            'PaymentTerms' => 'required|string|max:20',
            'Notes' => 'required|string|max:500',
            'Status' => 'required|integer|in:0,1',
        ]);
        
        try {
            // 3. Prepare Data Variables
            $userId = auth()->id() ?? 1; // Default to 1 if no user logged in
            $now = now();
            
            // Construct Display Name if empty
            $displayName = $request->DisplayName;
            if (empty($displayName)) {
                $displayName = $request->BusinessName;
            }

            // Construct Full Billing Address for the specific DB column
            $fullBillingAddress = $request->BillingAddress . ', ' . $request->BillingCity . ', ' . $request->BillingCountry;

            // 4. Construct Values Array 
            // MUST MATCH EXACTLY the order of columns in your Model's INSERT string
            $values_array = [
                0,                          // IsArchive (Default: Active)
                0,                          // VoucherID (Default)
                '0',                        // AccountNo (Default)
                $request->Title,            // Title
                $request->FirstName,        // FirstName
                $request->LastName,         // LastName
                $request->Email,            // Email
                $request->BusinessName,     // BusinessName
                0.00,                       // PartyBalance (Default)
                $request->Mobile,           // Mobile
                $request->Phone ?? '',      // Phone
                $displayName,               // DisplayName
                $request->Website ?? '',    // Website
                $request->Address,          // Address
                $request->City,             // City
                $request->Country,          // Country
                $request->County,           // County
                $request->PostalCode,       // PostalCode
                $request->BillingAddress,   // BillingAddress
                $request->BillingCity,      // BillingCity
                $request->BillingCountry,   // BillingCountry
                $request->BillingCounty,    // BillingCounty
                $request->BillingPostalCode,// BillingPostalCode
                $fullBillingAddress,        // FullBillingAddress
                $request->NTN,              // NTN
                $request->SalesTaxNumber ?? '', // SalesTtaxNumber (DB has Typo 'tt')
                $request->has('IsFiler') ? 1 : 0, // IsFiler
                $request->Notes,            // Notes
                $request->PaymentTerms,     // PaymentTerms
                $request->Status,           // Status
                $userId,                    // CreatedBy
                $now,                       // CreatedOn
                $userId,                    // UpdatedBy
                $now,                       // UpdatedOn
                $request->has('SameAsBilling') ? 1 : 0, // SameAsBilling
                $request->CNIC,             // CNIC
                $request->CreditLimit,      // CreditLimit
                null,                       // FieldA
                null,                       // FieldB
                null,                       // FieldC
                null,                       // FieldD
                null,                       // Field1
                null,                       // Field2
                null,                       // Field3
                null,                       // Field4
                0,                          // Typei
                'Customer',                 // Type
                0,                          // IsDashboard
                0                           // RequestID
            ];

            // 5. Get Query String from Model
            $query = Customer::save_transaction($validated);
            
            // 6. Execute Query using DB Facade
            DB::insert($query, $values_array);
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer created successfully!');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error saving customer: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the specified customer
     */
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return view('pages.dashboards.customers-show', compact('customer'));
    }
    
    /**
     * Show the form for editing the specified customer
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('pages.dashboards.customers-edit', compact('customer'));
    }
    
    /**
     * Update the specified customer - CALLS MODEL edit_transaction()
     */
    public function edit_transaction(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        // If "Same as Primary Address" is checked, copy primary address to billing
        if ($request->has('SameAsBilling') && $request->SameAsBilling == '1') {
            $request->merge([
                'BillingAddress' => $request->Address,
                'BillingCity' => $request->City,
                'BillingCountry' => $request->Country,
                'BillingCounty' => $request->County,
                'BillingPostalCode' => $request->PostalCode,
            ]);
        }
        
        // Validate the request
        $validated = $request->validate([
            'Title' => 'required|string|max:50',
            'FirstName' => 'required|string|max:100',
            'LastName' => 'required|string|max:100',
            'BusinessName' => 'required|string|max:200',
            'DisplayName' => 'nullable|string|max:200',  // NOT REQUIRED
            'Email' => 'required|email|max:500',
            'Mobile' => 'required|string|max:100',
            'Phone' => 'nullable|string|max:100',  // NOT REQUIRED
            'Website' => 'nullable|url|max:200',  // NOT REQUIRED
            'Address' => 'required|string|max:500',
            'City' => 'required|string|max:100',
            'Country' => 'required|string|max:100',
            'County' => 'required|string|max:100',
            'PostalCode' => 'required|string|max:20',
            'BillingAddress' => 'required|string|max:500',
            'BillingCity' => 'required|string|max:100',
            'BillingCountry' => 'required|string|max:100',
            'BillingCounty' => 'required|string|max:100',
            'BillingPostalCode' => 'required|string|max:20',
            'SameAsBilling' => 'nullable|boolean',
            'NTN' => 'required|string|max:20',
            'SalesTaxNumber' => 'nullable|string|max:50',  // NOT REQUIRED
            'IsFiler' => 'nullable|boolean',
            'CNIC' => 'required|string|max:50',
            'CreditLimit' => 'required|numeric|min:0',
            'PaymentTerms' => 'required|string|max:20',
            'Notes' => 'required|string|max:500',
            'Status' => 'required|integer|in:0,1',
        ], [
            // Custom error messages
            'Title.required' => 'Please select a title.',
            'FirstName.required' => 'First name is required.',
            'LastName.required' => 'Last name is required.',
            'BusinessName.required' => 'Business name is required.',
            'Email.required' => 'Email address is required.',
            'Email.email' => 'Please enter a valid email address.',
            'Mobile.required' => 'Mobile number is required.',
            'Address.required' => 'Street address is required.',
            'City.required' => 'City is required.',
            'Country.required' => 'Country is required.',
            'County.required' => 'State/Province is required.',
            'PostalCode.required' => 'Postal code is required.',
            'BillingAddress.required' => 'Billing address is required.',
            'BillingCity.required' => 'Billing city is required.',
            'BillingCountry.required' => 'Billing country is required.',
            'BillingCounty.required' => 'Billing state/province is required.',
            'BillingPostalCode.required' => 'Billing postal code is required.',
            'NTN.required' => 'NTN number is required.',
            'CNIC.required' => 'CNIC is required.',
            'CreditLimit.required' => 'Credit limit is required.',
            'CreditLimit.numeric' => 'Credit limit must be a number.',
            'PaymentTerms.required' => 'Payment terms are required.',
            'Notes.required' => 'Notes field is required.',
            'Status.required' => 'Status is required.',
        ]);
        
        try {
            // CALL MODEL FUNCTION (Instance)
            $result = $customer->edit_transaction();
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer updated successfully!');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error updating customer: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Archive/Delete the specified customer - CALLS MODEL delete_transaction()
     */
    public function delete_transaction($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            // CALL MODEL FUNCTION (Instance)
            $result = $customer->delete_transaction();
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Result: ' . $result);
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
}