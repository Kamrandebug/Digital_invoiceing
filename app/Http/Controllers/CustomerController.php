<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers (READ operation)
     */
    public function index()
    {
        // Fetch all active customers from the current tenant's database
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
     * SAVE TRANSACTION - Store a newly created customer (CREATE operation)
     * This function handles the creation of new customers
     */
    public function save_transaction(Request $request)
    {
        try {
            // Validate incoming data
            $validated = $request->validate([
                // Basic Information
                'Title' => 'nullable|string|max:50',
                'FirstName' => 'nullable|string|max:100',
                'LastName' => 'nullable|string|max:100',
                'BusinessName' => 'nullable|string|max:200',
                'DisplayName' => 'nullable|string|max:200',
                
                // Contact Information
                'Email' => 'nullable|email|max:500',
                'Mobile' => 'nullable|string|max:100',
                'Phone' => 'nullable|string|max:100',
                'Website' => 'nullable|url|max:200',
                
                // Shipping Address
                'Address' => 'nullable|string|max:500',
                'City' => 'nullable|string|max:100',
                'Country' => 'nullable|string|max:100',
                'County' => 'nullable|string|max:100',
                'PostalCode' => 'nullable|string|max:20',
                
                // Billing Address
                'BillingAddress' => 'nullable|string|max:500',
                'BillingCity' => 'nullable|string|max:100',
                'BillingCountry' => 'nullable|string|max:100',
                'BillingCounty' => 'nullable|string|max:100',
                'BillingPostalCode' => 'nullable|string|max:20',
                'SameAsBilling' => 'nullable|boolean',
                
                // Tax Information
                'NTN' => 'nullable|string|max:20',
                'SalesTaxNumber' => 'nullable|string|max:50',
                'IsFiler' => 'nullable|boolean',
                'CNIC' => 'nullable|string|max:50',
                
                // Financial Information
                'CreditLimit' => 'nullable|numeric|min:0',
                'PaymentTerms' => 'nullable|string|max:20',
                
                // Additional Information
                'Notes' => 'nullable|string|max:500',
                'Status' => 'nullable|integer|in:0,1',
            ]);
            
            // Auto-generate AccountNo if not provided
            if (!isset($validated['AccountNo'])) {
                $lastCustomer = Customer::orderBy('ID', 'desc')->first();
                $nextId = $lastCustomer ? $lastCustomer->ID + 1 : 1;
                $validated['AccountNo'] = 'CUST-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            }
            
            // Set DisplayName if not provided
            if (!isset($validated['DisplayName']) || empty($validated['DisplayName'])) {
                $validated['DisplayName'] = $validated['BusinessName'] 
                    ?? trim(($validated['FirstName'] ?? '') . ' ' . ($validated['LastName'] ?? ''));
            }
            
            // If SameAsBilling is checked, copy shipping to billing
            if (isset($validated['SameAsBilling']) && $validated['SameAsBilling']) {
                $validated['BillingAddress'] = $validated['Address'] ?? null;
                $validated['BillingCity'] = $validated['City'] ?? null;
                $validated['BillingCountry'] = $validated['Country'] ?? null;
                $validated['BillingCounty'] = $validated['County'] ?? null;
                $validated['BillingPostalCode'] = $validated['PostalCode'] ?? null;
            }
            
            // Build full billing address
            $billingParts = array_filter([
                $validated['BillingAddress'] ?? null,
                $validated['BillingCity'] ?? null,
                $validated['BillingCounty'] ?? null,
                $validated['BillingCountry'] ?? null,
                $validated['BillingPostalCode'] ?? null,
            ]);
            $validated['FullBillingAddress'] = implode(', ', $billingParts);
            
            // Set audit fields
            $validated['CreatedBy'] = Auth::id() ?? 1;
            $validated['CreatedOn'] = now();
            $validated['IsArchive'] = 0;
            
            // Set defaults for fields not in request
            $validated['PartyBalance'] = 0.00;
            $validated['IsFiler'] = $validated['IsFiler'] ?? 1;
            $validated['Status'] = $validated['Status'] ?? 1;
            $validated['Type'] = 1;
            $validated['Typei'] = 1;
            $validated['IsDashboard'] = 0;
            
            // Create the customer
            DB::beginTransaction();
            try {
                $customer = Customer::create($validated);
                DB::commit();
                
                return redirect()
                    ->route('customers.index')
                    ->with('success', 'Customer created successfully! Account No: ' . $customer->AccountNo);
                    
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error creating customer: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the specified customer (VIEW operation)
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
     * EDIT TRANSACTION - Update the specified customer (UPDATE operation)
     * This function handles updating existing customers
     */
    public function edit_transaction(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            // Validate incoming data
            $validated = $request->validate([
                // Basic Information
                'Title' => 'nullable|string|max:50',
                'FirstName' => 'nullable|string|max:100',
                'LastName' => 'nullable|string|max:100',
                'BusinessName' => 'nullable|string|max:200',
                'DisplayName' => 'nullable|string|max:200',
                
                // Contact Information
                'Email' => 'nullable|email|max:500',
                'Mobile' => 'nullable|string|max:100',
                'Phone' => 'nullable|string|max:100',
                'Website' => 'nullable|url|max:200',
                
                // Shipping Address
                'Address' => 'nullable|string|max:500',
                'City' => 'nullable|string|max:100',
                'Country' => 'nullable|string|max:100',
                'County' => 'nullable|string|max:100',
                'PostalCode' => 'nullable|string|max:20',
                
                // Billing Address
                'BillingAddress' => 'nullable|string|max:500',
                'BillingCity' => 'nullable|string|max:100',
                'BillingCountry' => 'nullable|string|max:100',
                'BillingCounty' => 'nullable|string|max:100',
                'BillingPostalCode' => 'nullable|string|max:20',
                'SameAsBilling' => 'nullable|boolean',
                
                // Tax Information
                'NTN' => 'nullable|string|max:20',
                'SalesTaxNumber' => 'nullable|string|max:50',
                'IsFiler' => 'nullable|boolean',
                'CNIC' => 'nullable|string|max:50',
                
                // Financial Information
                'CreditLimit' => 'nullable|numeric|min:0',
                'PaymentTerms' => 'nullable|string|max:20',
                
                // Additional Information
                'Notes' => 'nullable|string|max:500',
                'Status' => 'nullable|integer|in:0,1',
            ]);
            
            // Update DisplayName if not provided
            if (!isset($validated['DisplayName']) || empty($validated['DisplayName'])) {
                $validated['DisplayName'] = $validated['BusinessName'] 
                    ?? trim(($validated['FirstName'] ?? '') . ' ' . ($validated['LastName'] ?? ''));
            }
            
            // If SameAsBilling is checked, copy shipping to billing
            if (isset($validated['SameAsBilling']) && $validated['SameAsBilling']) {
                $validated['BillingAddress'] = $validated['Address'] ?? null;
                $validated['BillingCity'] = $validated['City'] ?? null;
                $validated['BillingCountry'] = $validated['Country'] ?? null;
                $validated['BillingCounty'] = $validated['County'] ?? null;
                $validated['BillingPostalCode'] = $validated['PostalCode'] ?? null;
            }
            
            // Build full billing address
            $billingParts = array_filter([
                $validated['BillingAddress'] ?? null,
                $validated['BillingCity'] ?? null,
                $validated['BillingCounty'] ?? null,
                $validated['BillingCountry'] ?? null,
                $validated['BillingPostalCode'] ?? null,
            ]);
            $validated['FullBillingAddress'] = implode(', ', $billingParts);
            
            // Set audit fields for update
            $validated['UpdatedBy'] = Auth::id() ?? 1;
            $validated['UpdatedOn'] = now();
            
            // Update the customer
            DB::beginTransaction();
            try {
                $customer->update($validated);
                DB::commit();
                
                return redirect()
                    ->route('customers.index')
                    ->with('success', 'Customer updated successfully!');
                    
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error updating customer: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * DELETE TRANSACTION - Archive/Delete the specified customer (DELETE operation)
     * This function soft deletes customers by setting IsArchive = 1
     */
    public function delete_transaction($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            DB::beginTransaction();
            try {
                // Soft delete by setting IsArchive
                $customer->update([
                    'IsArchive' => 1,
                    'UpdatedBy' => Auth::id() ?? 1,
                    'UpdatedOn' => now(),
                ]);
                
                DB::commit();
                
                return redirect()
                    ->route('customers.index')
                    ->with('success', 'Customer archived successfully!');
                    
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
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
            return redirect()
                ->back()
                ->with('error', 'Error restoring customer: ' . $e->getMessage());
        }
    }
    
    /**
     * Export customers to CSV
     */
    public function exportCSV()
    {
        $customers = Customer::active()->get();
        
        $filename = 'customers_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://output', 'w');
        
        // Set headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
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
                $customer->name,
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
        exit;
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
