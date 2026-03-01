<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        // Filters
        $filters = [
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        // Validation
        $rules = [];
        $messages = [];

        // Date rules
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $rules['start_date'] = 'date';
            $rules['end_date'] = 'date|after_or_equal:start_date';
            $messages['end_date.after_or_equal'] = 'End date must be after or equal to start date.';
        }

        // Search validation
        if ($request->filled('search')) {
            $rules['search'] = 'string|max:100|regex:/^[a-zA-Z0-9\s\-\.\,\@]+$/';
            $messages['search.regex'] = 'Search term contains invalid characters.';
            $messages['search.max'] = 'Search term is too long (max 100 chars).';
        }

        if (!empty($rules)) {
            $request->validate($rules, $messages);
        }

        // Get filtered data
        $customers = Customer::getlistingdata($filters)->paginate(15);
        
        return view('pages.dashboards.Customer.customers-list', compact('customers'));
    }
    
    /**
     * Create form
     */
    public function create()
    {
        return view('pages.dashboards.Customer.customers-form', ['mode' => 'create', 'customer' => new Customer()]);
    }
    
    /**
     * Store customer
     */
    public function save_transaction(Request $request)
    {
        // Format inputs
        $request->merge([
            'FirstName' => trim($request->FirstName ?? ''),
            'LastName' => trim($request->LastName ?? ''),
            'Email' => strtolower(trim($request->Email ?? '')),
            'Mobile' => preg_replace('/[^0-9+]/', '', $request->Mobile ?? ''),
            'Phone' => $request->Phone ? preg_replace('/[^0-9+]/', '', $request->Phone) : null,
        ]);
        
        // Handle Billing Address
        if ($request->has('SameAsBilling') && $request->SameAsBilling == '1') {
            $request->merge([
                'BillingAddress' => $request->Address,
                'BillingCity' => $request->City,
                'BillingCountry' => $request->Country,
                'BillingCounty' => $request->County,
                'BillingPostalCode' => $request->PostalCode,
            ]);
        }
        
  // ── Validate ──────────────────────────────────────────
try {
    $validated = $request->validate([
        'Title'             => 'required|string|in:Mr,Mrs,Miss,Ms,Dr,Prof,Engr',
        'FirstName'         => ['required','string','min:2','max:100','regex:/^[a-zA-Z\s\'-]+$/'],
        'LastName'          => ['required','string','min:2','max:100','regex:/^[a-zA-Z\s\'-]+$/'],
        'BusinessName'      => ['nullable','string','max:200','regex:/^[a-zA-Z0-9\s&.,\'()-]+$/'],
        'DisplayName'       => 'nullable|string|max:200',
        'Email'             => ['required','email','max:500','unique:crm_customer,Email'],
        'Mobile'            => ['required','string','regex:/^(\+92|0)?[0-9]{10}$/','unique:crm_customer,Mobile'],
        'Phone'             => ['nullable','string','regex:/^(\+92|0)?[0-9]{9,11}$/'],
        'Website'           => ['nullable','url','max:200','regex:/^https?:\/\//'],
        'Address'           => 'nullable|string|max:500',
        'City'              => ['nullable','string','max:100','regex:/^[a-zA-Z\s]+$/'],
        'County'            => ['nullable','string','max:100','regex:/^[a-zA-Z\s]+$/'],
        'PostalCode'        => ['nullable','string','regex:/^[0-9]{5}$/'],
        'Country'           => 'nullable|string|max:100',
        'BillingAddress'    => 'required_if:SameAsBilling,0|nullable|string|max:500',
        'BillingCity'       => ['required_if:SameAsBilling,0','nullable','string','max:100','regex:/^[a-zA-Z\s]+$/'],
        'BillingCounty'     => ['required_if:SameAsBilling,0','nullable','string','max:100','regex:/^[a-zA-Z\s]+$/'],
        'BillingPostalCode' => ['required_if:SameAsBilling,0','nullable','string','regex:/^[0-9]{5}$/'],
        'BillingCountry'    => 'required_if:SameAsBilling,0|nullable|string|max:100',
        'SameAsBilling'     => 'nullable|boolean',
        'NTN'               => ['nullable','string','regex:/^[0-9]{7}$/','unique:crm_customer,NTN'],
        'SalesTaxNumber'    => ['nullable','string','max:50','regex:/^[A-Z0-9\-]{5,50}$/','unique:crm_customer,SalesTaxNumber'],
        'IsFiler'           => 'nullable|boolean',
        'CNIC'              => ['required','string','regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/','unique:crm_customer,CNIC'],
        'CreditLimit'       => 'nullable|numeric|min:0|max:999999999999.99',
        'PaymentTerms'      => 'nullable|string|in:Cash,Net 15,Net 30,Net 45,Net 60,Due on Receipt,COD',
        'Notes'             => 'nullable|string|max:500',
        'Status'            => 'required|integer|in:0,1',
    ], [
        'Title.required'               => 'Please select a title.',
        'Title.in'                     => 'Invalid title selected.',
        'FirstName.required'           => 'First name is required.',
        'FirstName.min'                => 'First name must be at least 2 characters.',
        'FirstName.regex'              => 'First name can only contain letters, spaces, hyphens and apostrophes.',
        'LastName.required'            => 'Last name is required.',
        'LastName.min'                 => 'Last name must be at least 2 characters.',
        'LastName.regex'               => 'Last name can only contain letters, spaces, hyphens and apostrophes.',
        'BusinessName.regex'           => 'Business name contains invalid characters.',
        'Email.required'               => 'Email address is required.',
        'Email.email'                  => 'Please enter a valid email address.',
        'Email.unique'                 => 'This email address is already registered.',
        'Mobile.required'              => 'Mobile number is required.',
        'Mobile.regex'                 => 'Mobile must be Pakistan format (e.g., 03001234567).',
        'Mobile.unique'                => 'This mobile number is already registered.',
        'Phone.regex'                  => 'Phone number must be in Pakistan format.',
        'Website.url'                  => 'Please enter a valid website URL.',
        'Website.regex'                => 'Website must start with http:// or https://',
        'City.regex'                   => 'City name can only contain letters and spaces.',
        'County.regex'                 => 'State/Province can only contain letters and spaces.',
        'PostalCode.regex'             => 'Postal code must be exactly 5 digits.',
        'BillingAddress.required_if'   => 'Billing address is required.',
        'BillingCity.required_if'      => 'Billing city is required.',
        'BillingCity.regex'            => 'Billing city can only contain letters and spaces.',
        'BillingCounty.required_if'    => 'Billing state/province is required.',
        'BillingCounty.regex'          => 'Billing state/province can only contain letters and spaces.',
        'BillingPostalCode.required_if'=> 'Billing postal code is required.',
        'BillingPostalCode.regex'      => 'Billing postal code must be exactly 5 digits.',
        'BillingCountry.required_if'   => 'Billing country is required.',
        'NTN.regex'                    => 'NTN must be exactly 7 digits.',
        'NTN.unique'                   => 'This NTN is already registered.',
        'SalesTaxNumber.regex'         => 'Sales Tax Number format is invalid.',
        'SalesTaxNumber.unique'        => 'This Sales Tax Number is already registered.',
        'CNIC.required'                => 'CNIC is required.',
        'CNIC.regex'                   => 'CNIC must be in format: 12345-1234567-1',
        'CNIC.unique'                  => 'This CNIC is already registered.',
        'CreditLimit.numeric'          => 'Credit limit must be a valid number.',
        'CreditLimit.min'              => 'Credit limit cannot be negative.',
        'PaymentTerms.in'              => 'Invalid payment terms selected.',
        'Status.required'              => 'Status is required.',
        'Status.in'                    => 'Invalid status value.',
    ]);
} catch (\Illuminate\Validation\ValidationException $e) {
    if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
        return response()->json([
            'status'  => 'error',
            'errors'  => $e->errors(),
            'message' => 'Please fix the highlighted fields.',
        ], 422);
    }
    throw $e;
}
        
        try {
            // Generate UUID
            $requestId = (string) Str::uuid(); 
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString(); // SQL format: YYYY-MM-DD HH:MM:SS
            
            $displayName = $request->DisplayName ?: $request->BusinessName;
            $fullBillingAddress = $request->BillingAddress . ', ' . $request->BillingCity . ', ' . $request->BillingCountry;

            // Calculate ID
            $maxId = DB::table('temp_customer')->max('ID');
            $nextId = $maxId ? $maxId + 1 : 1;

            // Prepare data
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
                'SalesTaxNumber' => $request->SalesTaxNumber ?? '',
                'IsFiler' => $request->input('IsFiler', 0),
                'Notes' => $request->Notes,
                'PaymentTerms' => $request->input('PaymentTerms', 'Cash'),
                'Status' => $request->Status,
                'CreatedBy' => (int)$userId,
                'CreatedOn' => $now,
                'UpdatedBy' => (int)$userId,
                'UpdatedOn' => $now,
                'SameAsBilling' => $request->input('SameAsBilling', 0),
                'CNIC' => $request->CNIC,
                'CreditLimit' => $request->input('CreditLimit', 0),
                'FieldA' => null, 'FieldB' => null, 'FieldC' => null, 'FieldD' => null,
                'Field1' => null, 'Field2' => null, 'Field3' => null, 'Field4' => null,
                'Typei' => 0,
                'Type' => 1,
                'IsDashboard' => 0,
                'RequestID' => $requestId
            ];

            // Insert into temp
            \Log::info('Inserting Customer Data', ['data' => $data]);
            DB::table('temp_customer')->insert($data);

            // Execute model transaction (runs query inside model)
            Customer::save_transaction(['RequestID' => $requestId]);

                if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['status' => 'success', 'message' => 'Customer created successfully!']);
        }
        return redirect()->route('customers.index')->with('success', 'Customer created successfully!');

        } catch (\Exception $e) {
           if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
    return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
}
return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }

    }
    
    /**
     * Show customer
     */
    public function show($id)
    {
        // Read from crm_customer
        $customer = DB::table('crm_customer')->where('ID', $id)->first();
        
        if (!$customer) {
            abort(404, 'Customer not found in CRM');
        }
        
        // Hydrate model
        $customerModel = new Customer();
        $customerModel->setRawAttributes((array)$customer, true);
        $customerModel->exists = true;

        return view('pages.dashboards.Customer.customers-form', ['mode' => 'show', 'customer' => $customerModel]);
    }
    
    /**
     * Edit form
     */
    public function edit($id)
    {
        // Read from crm_customer
        $customer = DB::table('crm_customer')->where('ID', $id)->first();
        
        if (!$customer) {
            abort(404, 'Customer not found in CRM');
        }

        // Hydrate model
        $customerModel = new Customer();
        $customerModel->setRawAttributes((array)$customer, true);
        $customerModel->exists = true;

        return view('pages.dashboards.Customer.customers-form', ['mode' => 'edit', 'customer' => $customerModel]);
    }

    /**
 * Load any mode into modal via AJAX
 */
public function modal(Request $request, $id = null)
{
    $mode     = $request->query('mode', 'create');
    $customer = null;

    if ($id && in_array($mode, ['edit', 'show'])) {
        $customer = DB::table('crm_customer')->where('ID', $id)->first();

        if (!$customer) {
            return response('<div class="alert alert-danger m-3">Customer not found.</div>', 404);
        }

        // Hydrate into model so $customer->CreatedOn etc work as Carbon dates
        $customerModel = new Customer();
        $customerModel->setRawAttributes((array) $customer, true);
        $customerModel->exists = true;
        $customer = $customerModel;
    }

    // Return ONLY the partial — no layout wrapping
    return view('pages.dashboards.Customer._customer-form-partial', [
        'mode'     => $mode,
        'customer' => $customer ?? new Customer(),
        'inModal'  => true,
    ]);
}
    
    /**
     * Update customer
     */
    public function edit_transaction(Request $request, $id)
    {
        // Get existing
        $customer = DB::table('crm_customer')->where('ID', $id)->first();
        
        if (!$customer) {
            return redirect()->back()->with('error', 'Customer not found.');
        }
        
        // Format inputs
        $request->merge([
            'FirstName' => trim($request->FirstName ?? ''),
            'LastName' => trim($request->LastName ?? ''),
            'Email' => strtolower(trim($request->Email ?? '')),
            'Mobile' => preg_replace('/[^0-9+]/', '', $request->Mobile ?? ''),
            'Phone' => $request->Phone ? preg_replace('/[^0-9+]/', '', $request->Phone) : null,
        ]);
    
        // Handle Billing Address
        if ($request->has('SameAsBilling') && $request->SameAsBilling == '1') {
            $request->merge([
                'BillingAddress' => $request->Address,
                'BillingCity' => $request->City,
                'BillingCountry' => $request->Country,
                'BillingCounty' => $request->County,
                'BillingPostalCode' => $request->PostalCode,
            ]);
        }
        
     // ── Validate ──────────────────────────────────────────
try {
    $validated = $request->validate([
        'Title'             => 'required|string|in:Mr,Mrs,Miss,Ms,Dr,Prof,Engr',
        'FirstName'         => ['required','string','min:2','max:100','regex:/^[a-zA-Z\s\'-]+$/'],
        'LastName'          => ['required','string','min:2','max:100','regex:/^[a-zA-Z\s\'-]+$/'],
        'BusinessName'      => ['nullable','string','max:200','regex:/^[a-zA-Z0-9\s&.,\'()-]+$/'],
        'DisplayName'       => 'nullable|string|max:200',
        'Email'             => ['required','email','max:500', Rule::unique('crm_customer', 'Email')->ignore($id, 'ID')],
        'Mobile'            => ['required','string','regex:/^(\+92|0)?[0-9]{10}$/', Rule::unique('crm_customer', 'Mobile')->ignore($id, 'ID')],
        'Phone'             => ['nullable','string','regex:/^(\+92|0)?[0-9]{9,11}$/'],
        'Website'           => ['nullable','url','max:200','regex:/^https?:\/\//'],
        'Address'           => 'nullable|string|max:500',
        'City'              => ['nullable','string','max:100','regex:/^[a-zA-Z\s]+$/'],
        'County'            => ['nullable','string','max:100','regex:/^[a-zA-Z\s]+$/'],
        'PostalCode'        => ['nullable','string','regex:/^[0-9]{5}$/'],
        'Country'           => 'nullable|string|max:100',
        'BillingAddress'    => 'required_if:SameAsBilling,0|nullable|string|max:500',
        'BillingCity'       => ['required_if:SameAsBilling,0','nullable','string','max:100','regex:/^[a-zA-Z\s]+$/'],
        'BillingCounty'     => ['required_if:SameAsBilling,0','nullable','string','max:100','regex:/^[a-zA-Z\s]+$/'],
        'BillingPostalCode' => ['required_if:SameAsBilling,0','nullable','string','regex:/^[0-9]{5}$/'],
        'BillingCountry'    => 'required_if:SameAsBilling,0|nullable|string|max:100',
        'SameAsBilling'     => 'nullable|boolean',
        'NTN'               => ['nullable','string','regex:/^[0-9]{7}$/', Rule::unique('crm_customer', 'NTN')->ignore($id, 'ID')->whereNotNull('NTN')],
        'SalesTaxNumber'    => ['nullable','string','max:50','regex:/^[A-Z0-9\-]{5,50}$/', Rule::unique('crm_customer', 'SalesTaxNumber')->ignore($id, 'ID')->whereNotNull('SalesTaxNumber')],
        'IsFiler'           => 'nullable|boolean',
        'CNIC'              => ['required','string','regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/', Rule::unique('crm_customer', 'CNIC')->ignore($id, 'ID')],
        'CreditLimit'       => 'nullable|numeric|min:0|max:999999999999.99',
        'PaymentTerms'      => 'nullable|string|in:Cash,Net 15,Net 30,Net 45,Net 60,Due on Receipt,COD',
        'Notes'             => 'nullable|string|max:500',
        'Status'            => 'required|integer|in:0,1',
    ], [
        'Title.required'               => 'Please select a title.',
        'Title.in'                     => 'Invalid title selected.',
        'FirstName.required'           => 'First name is required.',
        'FirstName.min'                => 'First name must be at least 2 characters.',
        'FirstName.regex'              => 'First name can only contain letters, spaces, hyphens and apostrophes.',
        'LastName.required'            => 'Last name is required.',
        'LastName.min'                 => 'Last name must be at least 2 characters.',
        'LastName.regex'               => 'Last name can only contain letters, spaces, hyphens and apostrophes.',
        'BusinessName.regex'           => 'Business name contains invalid characters.',
        'Email.required'               => 'Email address is required.',
        'Email.email'                  => 'Please enter a valid email address.',
        'Email.unique'                 => 'This email address is already registered.',
        'Mobile.required'              => 'Mobile number is required.',
        'Mobile.regex'                 => 'Mobile must be Pakistan format (e.g., 03001234567).',
        'Mobile.unique'                => 'This mobile number is already registered.',
        'Phone.regex'                  => 'Phone number must be in Pakistan format.',
        'Website.url'                  => 'Please enter a valid website URL.',
        'Website.regex'                => 'Website must start with http:// or https://',
        'City.regex'                   => 'City name can only contain letters and spaces.',
        'County.regex'                 => 'State/Province can only contain letters and spaces.',
        'PostalCode.regex'             => 'Postal code must be exactly 5 digits.',
        'BillingAddress.required_if'   => 'Billing address is required.',
        'BillingCity.required_if'      => 'Billing city is required.',
        'BillingCity.regex'            => 'Billing city can only contain letters and spaces.',
        'BillingCounty.required_if'    => 'Billing state/province is required.',
        'BillingCounty.regex'          => 'Billing state/province can only contain letters and spaces.',
        'BillingPostalCode.required_if'=> 'Billing postal code is required.',
        'BillingPostalCode.regex'      => 'Billing postal code must be exactly 5 digits.',
        'BillingCountry.required_if'   => 'Billing country is required.',
        'NTN.regex'                    => 'NTN must be exactly 7 digits.',
        'NTN.unique'                   => 'This NTN is already registered.',
        'SalesTaxNumber.regex'         => 'Sales Tax Number format is invalid.',
        'SalesTaxNumber.unique'        => 'This Sales Tax Number is already registered.',
        'CNIC.required'                => 'CNIC is required.',
        'CNIC.regex'                   => 'CNIC must be in format: 12345-1234567-1',
        'CNIC.unique'                  => 'This CNIC is already registered.',
        'CreditLimit.numeric'          => 'Credit limit must be a valid number.',
        'CreditLimit.min'              => 'Credit limit cannot be negative.',
        'PaymentTerms.in'              => 'Invalid payment terms selected.',
        'Status.required'              => 'Status is required.',
        'Status.in'                    => 'Invalid status value.',
    ]);
} catch (\Illuminate\Validation\ValidationException $e) {
    if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
        return response()->json([
            'status'  => 'error',
            'errors'  => $e->errors(),
            'message' => 'Please fix the highlighted fields.',
        ], 422);
    }
    throw $e;
}
        
        try {
            // Generate UUID
            $requestId = (string) Str::uuid(); 
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();
            
            // Prepare values
            $displayName = $request->DisplayName ?: $request->BusinessName;
            $fullBillingAddress = $request->BillingAddress . ', ' . 
                                 $request->BillingCity . ', ' . 
                                 $request->BillingCountry;

            // Prepare data for temp_customer (edit flow needs a row with TID = $id and RequestID)
            $data = [
                'TID' => $id,
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
                'SalesTaxNumber' => $request->SalesTaxNumber ?? '',
                'IsFiler' => $request->input('IsFiler', 0),
                'Notes' => $request->Notes,
                'PaymentTerms' => $request->input('PaymentTerms', $customer->PaymentTerms ?? 'Cash'),
                'Status' => $request->Status,
                'CreatedBy' => (int)($customer->CreatedBy ?? $userId),
                'CreatedOn' => $customer->CreatedOn ?? $now,
                'UpdatedBy' => (int)$userId,
                'UpdatedOn' => $now,
                'SameAsBilling' => $request->input('SameAsBilling', 0),
                'CNIC' => $request->CNIC,
                'CreditLimit' => $request->input('CreditLimit', $customer->CreditLimit ?? 0),
                'FieldA' => null, 'FieldB' => null, 'FieldC' => null, 'FieldD' => null,
                'Field1' => null, 'Field2' => null, 'Field3' => null, 'Field4' => null,
                'Typei' => 0,
                'Type' => 1,
                'IsDashboard' => 0,
                'RequestID' => $requestId
            ];

            // Insert into temp (edit: no row with ID=$id exists in temp; we must insert one so model can read it)
            $nextTempId = DB::table('temp_customer')->max('ID');
            $nextTempId = $nextTempId ? (int)$nextTempId + 1 : 1;
            $data['ID'] = $nextTempId;
            DB::table('temp_customer')->insert($data);

            // Execute model transaction (runs query inside model)
            Customer::edit_transaction(['RequestID' => $requestId]);

           if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
    return response()->json(['status' => 'success', 'message' => 'Customer created successfully!']);
}
return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Edit transaction failed', [
                'error' => $e->getMessage(),
                'customer_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
    return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
}
return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }
    
    /**
     * Archive customer
     */
    public function delete_transaction($id)
    {
        try {
            // Read from crm_customer
            $customer = DB::table('crm_customer')->where('ID', $id)->first();
            
            if (!$customer) {
                return redirect()->back()->with('error', 'Customer not found.');
            }
            
            $requestId = (string) Str::uuid();
            $userId = auth()->id() ?? 1;
            $now = now()->toDateTimeString();

            // Insert a row into temp_customer so model's delete_transaction can read it (no row with ID=$id exists in temp)
            $nextTempId = DB::table('temp_customer')->max('ID');
            $nextTempId = $nextTempId ? (int)$nextTempId + 1 : 1;
            DB::table('temp_customer')->insert([
                'ID' => $nextTempId,
                'TID' => $id,
                'RequestID' => $requestId,
                'AccountNo' => $customer->AccountNo ?? '0',
                'Title' => $customer->Title ?? 'Mr',
                'FirstName' => $customer->FirstName ?? '',
                'Status' => 2,
                'CreatedBy' => (int)($customer->CreatedBy ?? $userId),
                'CreatedOn' => $customer->CreatedOn ?? $now,
                'UpdatedBy' => (int)$userId,
                'UpdatedOn' => $now,
            ]);

            // Execute model transaction (runs query inside model)
            Customer::delete_transaction(['RequestID' => $requestId]);
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer archived successfully!');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error deleting customer: ' . $e->getMessage());
        }
    }

    /**
     * Export CSV
     */
    public function exportCSV(Request $request)
    {
        $filename = 'customers-export-' . date('Y-m-d-His') . '.csv';
        
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        // Get filtered data
        $filters = [
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];
        
        $customers = Customer::getlistingdata($filters)->get();
        
        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');
            
            // Add BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($file, [
                'ID', 
                'Account No',
                'Name', 
                'Business Name',
                'Email',
                'Mobile',
                'Address', 
                'City',
                'Balance',
                'Status',
                'Created Date'
            ]);

            foreach ($customers as $customer) {
                // Name
                $name = $customer->DisplayName ?? ($customer->FirstName . ' ' . $customer->LastName);
                
                fputcsv($file, [
                    $customer->ID,
                    $customer->AccountNo,
                    $name,
                    $customer->BusinessName,
                    $customer->Email,
                    $customer->Mobile,
                    $customer->Address,
                    $customer->City,
                    number_format($customer->PartyBalance, 2),
                    $customer->Status == 1 ? 'Active' : 'Inactive',
                    $customer->CreatedOn
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}