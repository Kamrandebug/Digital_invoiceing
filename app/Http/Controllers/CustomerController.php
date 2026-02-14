<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        return view('pages.dashboards.customers-form', ['mode' => 'create', 'customer' => new Customer()]);
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
        // 1. Generate the Secure UUID
        $requestId = (string) Str::uuid(); 

        $userId = auth()->id() ?? 1;
        $now = now()->toDateTimeString(); // SQL format: YYYY-MM-DD HH:MM:SS
        
        $displayName = $request->DisplayName ?: $request->BusinessName;
        $fullBillingAddress = $request->BillingAddress . ', ' . $request->BillingCity . ', ' . $request->BillingCountry;

        // Calculate next ID (Fix for missing AUTO_INCREMENT)
        $maxId = DB::table('temp_customer')->max('ID');
        $nextId = $maxId ? $maxId + 1 : 1;

        // 2. The Values Array (Must be exactly 50 items with ID)
        $values_array = [
            $nextId,                    // 1. ID (New, Manual Increment)
            0,                          // 2. IsArchive
            0,                          // 3. VoucherID
            '0',                        // 4. AccountNo
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
            $request->SalesTaxNumber ?? '', // 26. SalesTtaxNumber (Double T)
            $request->input('IsFiler', 0),  // 27. IsFiler
            $request->Notes,            // 28. Notes
            $request->PaymentTerms,     // 29. PaymentTerms
            $request->Status,           // 30. Status
            (int)$userId,               // 31. CreatedBy
            $now,                       // 32. CreatedOn
            (int)$userId,               // 33. UpdatedBy
            $now,                       // 34. UpdatedOn
            $request->input('SameAsBilling', 0), // 35. SameAsBilling
            $request->CNIC,             // 36. CNIC
            $request->CreditLimit,      // 37. CreditLimit
            null, null, null, null,     // 38-41 (FieldA-D)
            null, null, null, null,     // 42-45 (Field1-4)
            0,                          // 46. Typei
            1,                          // 47. Type (Sent as Integer 1)
            0,                          // 48. IsDashboard
            $requestId                  // 49. RequestID (The UUID String)
        ];

        // 3. Get SQL and Execute
        $query = Customer::save_transaction($validated);
        DB::insert($query, $values_array);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer created successfully with Request ID: ' . $requestId);

    } catch (\Exception $e) {
        return redirect()
            ->back()
            ->with('error', 'Error: ' . $e->getMessage())
            ->withInput();
    }

}
    
    /**
     * Display the specified customer
     */
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return view('pages.dashboards.customers-form', ['mode' => 'show', 'customer' => $customer]);
    }
    
    /**
     * Show the form for editing the specified customer
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('pages.dashboards.customers-form', ['mode' => 'edit', 'customer' => $customer]);
    }
    
    /**
     * Update the specified customer - CALLS MODEL edit_transaction()
     */
    public function edit_transaction(Request $request, $id)
{
    // Get existing customer for preservation of original values
    $customer = Customer::findOrFail($id);
    
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
    ], [
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
        // Generate UUID for tracking
        $requestId = (string) Str::uuid(); 
        $userId = auth()->id() ?? 1;
        $now = now()->toDateTimeString();
        
        // Prepare computed values
        $displayName = $request->DisplayName ?: $request->BusinessName;
        $fullBillingAddress = $request->BillingAddress . ', ' . 
                             $request->BillingCity . ', ' . 
                             $request->BillingCountry;

        // ========================================================================
        // FIXED FOR UPDATE QUERY: Parameters match UPDATE SET clause order
        // CustomerID goes at the END for WHERE clause
        // ========================================================================
        
        // Update customer model properties (needed for Model's edit_transaction method)
        $customer->IsArchive = 0;
        $customer->VoucherID = $customer->VoucherID ?? 0;
        $customer->AccountNo = $customer->AccountNo ?? '0';
        $customer->Title = $request->Title;
        $customer->FirstName = $request->FirstName;
        $customer->LastName = $request->LastName;
        $customer->Email = $request->Email;
        $customer->BusinessName = $request->BusinessName;
        $customer->PartyBalance = $customer->PartyBalance ?? 0.00;
        $customer->Mobile = $request->Mobile;
        $customer->Phone = $request->Phone ?? '';
        $customer->DisplayName = $displayName;
        $customer->Website = $request->Website ?? '';
        $customer->Address = $request->Address;
        $customer->City = $request->City;
        $customer->Country = $request->Country;
        $customer->County = $request->County;
        $customer->PostalCode = $request->PostalCode;
        $customer->BillingAddress = $request->BillingAddress;
        $customer->BillingCity = $request->BillingCity;
        $customer->BillingCountry = $request->BillingCountry;
        $customer->BillingCounty = $request->BillingCounty;
        $customer->BillingPostalCode = $request->BillingPostalCode;
        $customer->FullBillingAddress = $fullBillingAddress;
        $customer->NTN = $request->NTN;
        $customer->SalesTtaxNumber = $request->SalesTaxNumber ?? '';
        $customer->IsFiler = $request->input('IsFiler', 0);
        $customer->Notes = $request->Notes;
        $customer->PaymentTerms = $request->PaymentTerms;
        $customer->Status = $request->Status;
        $customer->UpdatedBy = $userId;
        $customer->UpdatedOn = $now;
        $customer->SameAsBilling = $request->input('SameAsBilling', 0);
        $customer->CNIC = $request->CNIC;
        $customer->CreditLimit = $request->CreditLimit;
        $customer->RequestID = $requestId;
        
        // Get SQL query from Model (instance method that uses $this properties)
        $query = $customer->edit_transaction();
        
        // Build values array - 49 parameters total
        // ORDER MATCHES: UPDATE temp_customer SET field1=?, field2=?, ... WHERE ID=?
        // CustomerID is LAST (for WHERE clause), NOT FIRST
        $values_array = [
            // SET clause parameters (48 params)
            0,                                  // 1. IsArchive
            $customer->VoucherID ?? 0,          // 2. VoucherID (preserve)
            $customer->AccountNo ?? '0',        // 3. AccountNo (preserve)
            $request->Title,                    // 4. Title
            $request->FirstName,                // 5. FirstName
            $request->LastName,                 // 6. LastName
            $request->Email,                    // 7. Email
            $request->BusinessName,             // 8. BusinessName
            $customer->PartyBalance ?? 0.00,    // 9. PartyBalance (preserve)
            $request->Mobile,                   // 10. Mobile
            $request->Phone ?? '',              // 11. Phone
            $displayName,                       // 12. DisplayName
            $request->Website ?? '',            // 13. Website
            $request->Address,                  // 14. Address
            $request->City,                     // 15. City
            $request->Country,                  // 16. Country
            $request->County,                   // 17. County
            $request->PostalCode,               // 18. PostalCode
            $request->BillingAddress,           // 19. BillingAddress
            $request->BillingCity,              // 20. BillingCity
            $request->BillingCountry,           // 21. BillingCountry
            $request->BillingCounty,            // 22. BillingCounty
            $request->BillingPostalCode,        // 23. BillingPostalCode
            $fullBillingAddress,                // 24. FullBillingAddress
            $request->NTN,                      // 25. NTN
            $request->SalesTaxNumber ?? '',     // 26. SalesTtaxNumber (note double T)
            $request->input('IsFiler', 0),      // 27. IsFiler
            $request->Notes,                    // 28. Notes
            $request->PaymentTerms,             // 29. PaymentTerms
            $request->Status,                   // 30. Status
            (int)$userId,                       // 31. UpdatedBy
            $now,                               // 32. UpdatedOn
            $request->input('SameAsBilling', 0),// 33. SameAsBilling
            $request->CNIC,                     // 34. CNIC
            $request->CreditLimit,              // 35. CreditLimit
            null, null, null, null,             // 36-39 (FieldA-D)
            null, null, null, null,             // 40-43 (Field1-4)
            0,                                  // 44. Typei
            1,                                  // 45. Type
            0,                                  // 46. IsDashboard
            $requestId,                         // 47. RequestID
            
            // WHERE clause parameter (1 param)
            (int)$id                            // 48. ID (for WHERE clause - MUST BE LAST)
        ];

        // Execute the update
        DB::update($query, $values_array);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer updated successfully with Request ID: ' . $requestId);
            
    } catch (\Exception $e) {
        \Log::error('Edit transaction failed', [
            'error' => $e->getMessage(),
            'customer_id' => $id,
            'trace' => $e->getTraceAsString()
        ]);
        
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
        
        // Get SQL query from Model
        $query = $customer->delete_transaction();
        
        // Execute the UPDATE query with customer ID as parameter
        DB::update($query, [(int)$id]);
        
        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer archived successfully!');
            
    } catch (\Exception $e) {
        return redirect()
            ->back()
            ->with('error', 'Error deleting customer: ' . $e->getMessage());
    }
}
}