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
        $userId = auth()->id() ?? 1; 
        $now = now()->toDateTimeString(); // FIX: Convert Carbon object to string for Raw SQL
        
        $displayName = $request->DisplayName ?: $request->BusinessName;
        $fullBillingAddress = $request->BillingAddress . ', ' . $request->BillingCity . ', ' . $request->BillingCountry;

        // 4. Construct Values Array 
        // MUST MATCH EXACTLY the order of columns in your Model's INSERT string
        $values_array = [
            0,                          // 1. IsArchive
            0,                          // 2. VoucherID
            '0',                        // 3. AccountNo
            $request->Title,            // 4. Title
            $request->FirstName,        // 5. FirstName
            $request->LastName,         // 6. LastName
            $request->Email,            // 7. Email
            $request->BusinessName,     // 8. BusinessName
            0.00,                       // 9. PartyBalance
            $request->Mobile,           // 10. Mobile
            $request->Phone ?? '',      // 11. Phone
            $displayName,               // 12. DisplayName
            $request->Website ?? '',    // 13. Website
            $request->Address,          // 14. Address
            $request->City,             // 15. City
            $request->Country,          // 16. Country
            $request->County,           // 17. County
            $request->PostalCode,       // 18. PostalCode
            $request->BillingAddress,   // 19. BillingAddress
            $request->BillingCity,      // 20. BillingCity
            $request->BillingCountry,   // 21. BillingCountry
            $request->BillingCounty,    // 22. BillingCounty
            $request->BillingPostalCode,// 23. BillingPostalCode
            $fullBillingAddress,        // 24. FullBillingAddress
            $request->NTN,              // 25. NTN
            $request->SalesTaxNumber ?? '', // 26. SalesTtaxNumber (Double T in DB)
            $request->input('IsFiler', 0),  // 27. IsFiler (Integer)
            $request->Notes,            // 28. Notes
            $request->PaymentTerms,     // 29. PaymentTerms
            $request->Status,           // 30. Status
            (int)$userId,               // 31. CreatedBy (Integer)
            $now,                       // 32. CreatedOn
            (int)$userId,               // 33. UpdatedBy (Integer)
            $now,                       // 34. UpdatedOn
            $request->input('SameAsBilling', 0), // 35. SameAsBilling (Integer)
            $request->CNIC,             // 36. CNIC
            $request->CreditLimit,      // 37. CreditLimit
            null,                       // 38. FieldA
            null,                       // 39. FieldB
            null,                       // 40. FieldC
            null,                       // 41. FieldD
            null,                       // 42. Field1
            null,                       // 43. Field2
            null,                       // 44. Field3
            null,                       // 45. Field4
            0,                          // 46. Typei
            1,                          // 47. Type (FIXED: Sent 1 instead of 'Customer')
            0,                          // 48. IsDashboard
            0                           // 49. RequestID
        ];

        // 5. Get Query String from Model
        $query = Customer::save_transaction($validated);
        
        // 6. Execute Query
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