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
        // 1. Generate the Secure UUID
        $requestId = (string) Str::uuid(); 

        $userId = auth()->id() ?? 1;
        $now = now()->toDateTimeString(); // SQL format: YYYY-MM-DD HH:MM:SS
        
        $displayName = $request->DisplayName ?: $request->BusinessName;
        $fullBillingAddress = $request->BillingAddress . ', ' . $request->BillingCity . ', ' . $request->BillingCountry;

        // 2. The Values Array (Must be exactly 49 items)
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
            // ========================================================================
            // CRITICAL FIX: Update customer properties BEFORE calling edit_transaction
            // ========================================================================
            
            // Generate UUID for tracking
            $requestId = (string) Str::uuid(); 
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();
            
            // Prepare computed values
            $displayName = $request->DisplayName ?: $request->BusinessName;
            $fullBillingAddress = $request->BillingAddress . ', ' . 
                                 $request->BillingCity . ', ' . 
                                 $request->BillingCountry;

            // Update customer object properties with validated data
            // This is necessary because edit_transaction() is an INSTANCE method
            // that likely uses $this->property to build the SQL query
            $customer->Title = $request->Title;
            $customer->FirstName = $request->FirstName;
            $customer->LastName = $request->LastName;
            $customer->Email = $request->Email;
            $customer->BusinessName = $request->BusinessName;
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
            $customer->SalesTaxNumber = $request->SalesTaxNumber ?? '';
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

            // ========================================================================
            // Get SQL query from INSTANCE method (not static!)
            // ========================================================================
            $query = $customer->edit_transaction();  // ← INSTANCE method call
            
            // Build values array for the stored procedure
            $values_array = [
                $customer->ID,                      // 1. CustomerID (MUST BE FIRST)
                0,                                  // 2. IsArchive
                $customer->VoucherID ?? 0,          // 3. VoucherID
                $customer->AccountNo ?? '0',        // 4. AccountNo
                $customer->Title,                   // 5. Title
                $customer->FirstName,               // 6. FirstName
                $customer->LastName,                // 7. LastName
                $customer->Email,                   // 8. Email
                $customer->BusinessName,            // 9. BusinessName
                $customer->PartyBalance ?? 0.00,    // 10. PartyBalance
                $customer->Mobile,                  // 11. Mobile
                $customer->Phone,                   // 12. Phone
                $customer->DisplayName,             // 13. DisplayName
                $customer->Website,                 // 14. Website
                $customer->Address,                 // 15. Address
                $customer->City,                    // 16. City
                $customer->Country,                 // 17. Country
                $customer->County,                  // 18. County
                $customer->PostalCode,              // 19. PostalCode
                $customer->BillingAddress,          // 20. BillingAddress
                $customer->BillingCity,             // 21. BillingCity
                $customer->BillingCountry,          // 22. BillingCountry
                $customer->BillingCounty,           // 23. BillingCounty
                $customer->BillingPostalCode,       // 24. BillingPostalCode
                $customer->FullBillingAddress,      // 25. FullBillingAddress
                $customer->NTN,                     // 26. NTN
                $customer->SalesTaxNumber,          // 27. SalesTaxNumber
                $customer->IsFiler,                 // 28. IsFiler
                $customer->Notes,                   // 29. Notes
                $customer->PaymentTerms,            // 30. PaymentTerms
                $customer->Status,                  // 31. Status
                $customer->CreatedBy ?? $userId,    // 32. CreatedBy (preserve)
                $customer->CreatedOn ?? $now,       // 33. CreatedOn (preserve)
                $customer->UpdatedBy,               // 34. UpdatedBy
                $customer->UpdatedOn,               // 35. UpdatedOn
                $customer->SameAsBilling,           // 36. SameAsBilling
                $customer->CNIC,                    // 37. CNIC
                $customer->CreditLimit,             // 38. CreditLimit
                null, null, null, null,             // 39-42 (FieldA-D)
                null, null, null, null,             // 43-46 (Field1-4)
                0,                                  // 47. Typei
                1,                                  // 48. Type
                0,                                  // 49. IsDashboard
                $customer->RequestID                // 50. RequestID
            ];

            // Execute the update query
            DB::update($query, $values_array);

            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer updated successfully with Request ID: ' . $requestId);
                
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