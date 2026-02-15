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
    // $validated = $request->validate([
    //     'Title' => 'required|string|max:50',
    //     'FirstName' => 'required|string|max:100',
    //     'LastName' => 'required|string|max:100',
    //     'BusinessName' => 'required|string|max:200',
    //     'DisplayName' => 'nullable|string|max:200',
    //     'Email' => 'required|email|max:500',
    //     'Mobile' => 'required|string|max:100',
    //     'Phone' => 'nullable|string|max:100',
    //     'Website' => 'nullable|url|max:200',
    //     'Address' => 'required|string|max:500',
    //     'City' => 'required|string|max:100',
    //     'Country' => 'required|string|max:100',
    //     'County' => 'required|string|max:100',
    //     'PostalCode' => 'required|string|max:20',
    //     'BillingAddress' => 'required|string|max:500',
    //     'BillingCity' => 'required|string|max:100',
    //     'BillingCountry' => 'required|string|max:100',
    //     'BillingCounty' => 'required|string|max:100',
    //     'BillingPostalCode' => 'required|string|max:20',
    //     'SameAsBilling' => 'nullable|boolean',
    //     'NTN' => 'required|string|max:20',
    //     'SalesTaxNumber' => 'nullable|string|max:50',
    //     'IsFiler' => 'nullable|boolean',
    //     'CNIC' => 'required|string|max:50',
    //     'CreditLimit' => 'required|numeric|min:0',
    //     'PaymentTerms' => 'required|string|max:20',
    //     'Notes' => 'required|string|max:500',
    //     'Status' => 'required|integer|in:0,1',
    // ]);
    
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

        // 2. The Values Array (Associative) for easy insertion
        $data = [
            'ID' => $nextId,
            'TID' => $nextId,
            'IsArchive' => 0,
            'VoucherID' => 0,
            'AccountNo' => 'CUST-' . str_pad($nextId, 6, '0', STR_PAD_LEFT), // Generate Unique AccountNo
            'Title' => $request->Title,
            'FirstName' => $request->FirstName,
            'LastName' => $request->LastName,
            'Email' => $request->Email,
            'BusinessName' => $request->BusinessName,
            'PartyBalance' => 0.00,
            'Mobile' => $request->Mobile,
            'Phone' => $request->Phone ?? '',
            'DisplayName' => $displayName,
            'Website' => $request->Website ?? '',
            'Address' => $request->Address,
            'City' => $request->City,
            'Country' => $request->Country,
            'County' => $request->County,
            'PostalCode' => $request->PostalCode,
            'BillingAddress' => $request->BillingAddress,
            'BillingCity' => $request->BillingCity,
            'BillingCountry' => $request->BillingCountry,
            'BillingCounty' => $request->BillingCounty,
            'BillingPostalCode' => $request->BillingPostalCode,
            'FullBillingAddress' => $fullBillingAddress,
            'NTN' => $request->NTN,
            'SalesTtaxNumber' => $request->SalesTaxNumber ?? '',
            'IsFiler' => $request->input('IsFiler', 0),
            'Notes' => $request->Notes,
            'PaymentTerms' => $request->PaymentTerms,
            'Status' => $request->Status,
            'CreatedBy' => (int)$userId,
            'CreatedOn' => $now,
            'UpdatedBy' => (int)$userId,
            'UpdatedOn' => $now,
            'SameAsBilling' => $request->input('SameAsBilling', 0),
            'CNIC' => $request->CNIC,
            'CreditLimit' => $request->CreditLimit,
            'FieldA' => null, 'FieldB' => null, 'FieldC' => null, 'FieldD' => null,
            'Field1' => null, 'Field2' => null, 'Field3' => null, 'Field4' => null,
            'Typei' => 0,
            'Type' => 1,
            'IsDashboard' => 0,
            'RequestID' => $requestId
        ];

        // 3. Drop into temp_customer table
        \Log::info('Inserting Customer Data', ['data' => $data]);
        DB::table('temp_customer')->insert($data);

        // 4. Call model function (as requested, even if empty/dummy)
        // Pass RequestID because model expects it
        $query = Customer::save_transaction(['RequestID' => $requestId]);
        
        // Execute the returned query if it's not empty
        if (!empty($query) && $query !== "Empty") {
            DB::statement($query);
        }

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
        // $validated = $request->validate([
        // ... (validation rules)
        // ]);
        
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

            // 3. Prepare Data for Update (Associative Array)
            $data = [
                'TID' => $id, // Ensure TID matches ID on update
                'IsArchive' => 0,
                'VoucherID' => $customer->VoucherID ?? 0,
                'AccountNo' => $customer->AccountNo ?? '0',
                'Title' => $request->Title,
                'FirstName' => $request->FirstName,
                'LastName' => $request->LastName,
                'Email' => $request->Email,
                'BusinessName' => $request->BusinessName,
                'PartyBalance' => $customer->PartyBalance ?? 0.00,
                'Mobile' => $request->Mobile,
                'Phone' => $request->Phone ?? '',
                'DisplayName' => $displayName,
                'Website' => $request->Website ?? '',
                'Address' => $request->Address,
                'City' => $request->City,
                'Country' => $request->Country,
                'County' => $request->County,
                'PostalCode' => $request->PostalCode,
                'BillingAddress' => $request->BillingAddress,
                'BillingCity' => $request->BillingCity,
                'BillingCountry' => $request->BillingCountry,
                'BillingCounty' => $request->BillingCounty,
                'BillingPostalCode' => $request->BillingPostalCode,
                'FullBillingAddress' => $fullBillingAddress,
                'NTN' => $request->NTN,
                'SalesTtaxNumber' => $request->SalesTaxNumber ?? '',
                'IsFiler' => $request->input('IsFiler', 0),
                'Notes' => $request->Notes,
                'PaymentTerms' => $request->PaymentTerms,
                'Status' => $request->Status,
                'UpdatedBy' => (int)$userId,
                'UpdatedOn' => $now,
                'SameAsBilling' => $request->input('SameAsBilling', 0),
                'CNIC' => $request->CNIC,
                'CreditLimit' => $request->CreditLimit,
                'FieldA' => null, 'FieldB' => null, 'FieldC' => null, 'FieldD' => null,
                'Field1' => null, 'Field2' => null, 'Field3' => null, 'Field4' => null,
                'Typei' => 0,
                'Type' => 1,
                'IsDashboard' => 0,
                'RequestID' => $requestId
            ];

            // 4. Update temp_customer table
            DB::table('temp_customer')->where('ID', $id)->update($data);

            // 5. Call model function (as requested)
            $query = Customer::edit_transaction(['RequestID' => $requestId]);

            // Execute the returned query if it's not empty
            if (!empty($query) && $query !== "Empty") {
                 DB::statement($query);
            }

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
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            
            // 1. Update temp_customer table directly (Archive)
            // We MUST update RequestID so the model query can find it
            DB::table('temp_customer')
                ->where('ID', $id)
                ->update([
                    'IsArchive' => 1, 
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => now()->toDateTimeString(),
                    'RequestID' => $requestId 
                ]);
            
            // 2. Call model function (as requested)
            // Pass RequestID to the model method
            $query = $customer->delete_transaction(['RequestID' => $requestId]);
            
            if (!empty($query) && $query !== "Empty") {
                DB::statement($query);
            }
            
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