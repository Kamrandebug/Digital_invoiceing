<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // Imported correctly at the top

class CustomerController extends Controller
{
    /**
     * Display a listing of customers (READ operation)
     */
    public function index()
    {
        // Fetch all active customers
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
     * SAVE TRANSACTION - Store a newly created customer
     */
    public function save_transaction(Request $request)
    {
        try {
            // --- VALIDATION RULES (CREATE) ---
            $validated = $request->validate([
                // 1. Basic Identity
                'Title' => ['nullable', 'string', 'max:50', Rule::in(['Mr', 'Mrs', 'Ms', 'Dr', 'Prof', 'Engr', 'Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Prof.', 'Engr.'])],
                
                // Regex: Letters, spaces, apostrophes, hyphens only. No numbers.
                'FirstName' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s\'-]+$/'],
                'LastName'  => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s\'-]+$/'],
                
                // Regex: Alphanumeric + & . , - ' ( )
                'BusinessName' => ['nullable', 'string', 'max:200', 'regex:/^[a-zA-Z0-9\s&.,\'()-]+$/'],
                'DisplayName'  => ['nullable', 'string', 'max:200'],

                // 2. Contact Information
                'Email'   => ['required', 'string', 'email', 'max:500', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', Rule::unique('customers', 'Email')],
                'Mobile'  => ['required', 'string', 'max:100', 'regex:/^(\+92|0)?[0-9]{10}$/', Rule::unique('customers', 'Mobile')],
                'Phone'   => ['nullable', 'string', 'max:100', 'regex:/^(\+92|0)?[0-9]{9,11}$/'],
                'Website' => ['nullable', 'url', 'max:200', 'regex:/^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b/'],

                // 3. Address Information (No HTML)
                'Address'    => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9\s,.\-#\/]+$/'],
                'City'       => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
                'Country'    => ['nullable', 'string', 'max:100'],
                'County'     => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
                'PostalCode' => ['nullable', 'numeric', 'digits:5'],

                // 4. Financial & Tax (Sensitive)
                'NTN'            => ['nullable', 'numeric', 'digits:7', Rule::unique('customers', 'NTN')],
                'SalesTaxNumber' => ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9-]{5,50}$/', Rule::unique('customers', 'SalesTaxNumber')],
                'CNIC'           => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/', Rule::unique('customers', 'CNIC')],
                'CreditLimit'    => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
                
                'PaymentTerms' => ['nullable', 'string', 'max:20', Rule::in(['Net 15', 'Net 30', 'Net 45', 'Net 60', 'Due on Receipt', 'COD'])],
                'IsFiler'      => ['required', 'boolean'],

                // 5. System
                'Status' => ['required', 'integer', 'in:0,1'],
                'Notes'  => ['nullable', 'string', 'max:500', 'not_regex:/<[^>]*>/'], // Prevent HTML
            ]);
            
            // Delegate logic to Model
            $customer = Customer::save_transaction($validated);
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer created successfully! Account No: ' . $customer->AccountNo);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating customer: ' . $e->getMessage())->withInput();
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
        return view('pages.dashboards.customers-create', compact('customer'));
    }
    
    /**
     * EDIT TRANSACTION - Update the specified customer
     */
    public function edit_transaction(Request $request, $id)
    {
        try {
            // --- VALIDATION RULES (UPDATE) ---
            // Note: We use ->ignore($id, 'ID') to allow the current user to keep their own unique values
            $validated = $request->validate([
                // 1. Basic Identity
                'Title' => ['nullable', 'string', 'max:50', Rule::in(['Mr', 'Mrs', 'Ms', 'Dr', 'Prof', 'Engr', 'Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Prof.', 'Engr.'])],
                
                'FirstName' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s\'-]+$/'],
                'LastName'  => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s\'-]+$/'],
                
                'BusinessName' => ['nullable', 'string', 'max:200', 'regex:/^[a-zA-Z0-9\s&.,\'()-]+$/'],
                'DisplayName'  => ['nullable', 'string', 'max:200'],

                // 2. Contact Information
                'Email'   => ['required', 'string', 'email', 'max:500', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', Rule::unique('customers', 'Email')->ignore($id, 'ID')],
                'Mobile'  => ['required', 'string', 'max:100', 'regex:/^(\+92|0)?[0-9]{10}$/', Rule::unique('customers', 'Mobile')->ignore($id, 'ID')],
                'Phone'   => ['nullable', 'string', 'max:100', 'regex:/^(\+92|0)?[0-9]{9,11}$/'],
                'Website' => ['nullable', 'url', 'max:200', 'regex:/^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b/'],

                // 3. Address Information
                'Address'    => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9\s,.\-#\/]+$/'],
                'City'       => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
                'Country'    => ['nullable', 'string', 'max:100'],
                'County'     => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
                'PostalCode' => ['nullable', 'numeric', 'digits:5'],

                // 4. Financial & Tax
                'NTN'            => ['nullable', 'numeric', 'digits:7', Rule::unique('customers', 'NTN')->ignore($id, 'ID')],
                'SalesTaxNumber' => ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9-]{5,50}$/', Rule::unique('customers', 'SalesTaxNumber')->ignore($id, 'ID')],
                'CNIC'           => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/', Rule::unique('customers', 'CNIC')->ignore($id, 'ID')],
                'CreditLimit'    => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
                
                'PaymentTerms' => ['nullable', 'string', 'max:20', Rule::in(['Net 15', 'Net 30', 'Net 45', 'Net 60', 'Due on Receipt', 'COD'])],
                'IsFiler'      => ['required', 'boolean'],

                // 5. System
                'Status' => ['required', 'integer', 'in:0,1'],
                'Notes'  => ['nullable', 'string', 'max:500', 'not_regex:/<[^>]*>/'],
            ]);
            
            // Delegate logic to Model
            $customer = Customer::edit_transaction($id, $validated);
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer updated successfully!');
                    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating customer: ' . $e->getMessage())->withInput();
        }
    }
    
    /**
     * DELETE TRANSACTION - Archive/Delete the specified customer
     */
    public function delete_transaction($id)
    {
        try {
            // Call model method to handle all business logic
            Customer::delete_transaction($id);
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer archived successfully!');
                    
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error archiving customer: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore an archived customer
     */
    public function restore($id)
    {
        try {
            $customer = Customer::withArchived()->findOrFail($id);
            
            DB::beginTransaction();
            try {
                $customer->update([
                    'IsArchive' => 0,
                    'UpdatedBy' => Auth::id() ?? 1,
                    'UpdatedOn' => now(),
                ]);
                
                DB::commit();
                
                return redirect()
                    ->route('customers.index')
                    ->with('success', 'Customer restored successfully!');
                    
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error restoring customer: ' . $e->getMessage());
        }
    }
    
    /**
     * Export customers to CSV
     */
    public function exportCSV()
    {
        $customers = Customer::active()->get();
        
        $filename = 'customers_' . date('Y-m-d_His') . '.csv';
        
        return response()->streamDownload(function () use ($customers) {
            $handle = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($handle, [
                'ID', 'Account No', 'Name', 'Business Name', 'Email', 'Mobile', 'Phone', 
                'Address', 'City', 'Country', 'Balance', 'Credit Limit', 'Status'
            ]);
            
            // Add data
            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->ID,
                    $customer->AccountNo,
                    $customer->DisplayName ?? ($customer->FirstName . ' ' . $customer->LastName),
                    $customer->BusinessName,
                    $customer->Email,
                    $customer->Mobile,
                    $customer->Phone,
                    $customer->Address,
                    $customer->City,
                    $customer->Country,
                    $customer->PartyBalance,
                    $customer->CreditLimit,
                    $customer->Status == 1 ? 'Active' : 'Inactive',
                ]);
            }
            
            fclose($handle);
        }, $filename);
    }
    
    /**
     * Get customer details via AJAX
     */
    public function getCustomerData($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
    }
}