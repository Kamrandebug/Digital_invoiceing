<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

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
        try {
            $validated = $request->validate([
                'Title' => 'nullable|string|max:50',
                'FirstName' => 'nullable|string|max:100',
                'LastName' => 'nullable|string|max:100',
                'BusinessName' => 'nullable|string|max:200',
                'DisplayName' => 'nullable|string|max:200',
                'Email' => 'nullable|email|max:500',
                'Mobile' => 'nullable|string|max:100',
                'Phone' => 'nullable|string|max:100',
                'Website' => 'nullable|url|max:200',
                'Address' => 'nullable|string|max:500',
                'City' => 'nullable|string|max:100',
                'Country' => 'nullable|string|max:100',
                'County' => 'nullable|string|max:100',
                'PostalCode' => 'nullable|string|max:20',
                'BillingAddress' => 'nullable|string|max:500',
                'BillingCity' => 'nullable|string|max:100',
                'BillingCountry' => 'nullable|string|max:100',
                'BillingCounty' => 'nullable|string|max:100',
                'BillingPostalCode' => 'nullable|string|max:20',
                'SameAsBilling' => 'nullable|boolean',
                'NTN' => 'nullable|string|max:20',
                'SalesTaxNumber' => 'nullable|string|max:50',
                'IsFiler' => 'nullable|boolean',
                'CNIC' => 'nullable|string|max:50',
                'CreditLimit' => 'nullable|numeric|min:0',
                'PaymentTerms' => 'nullable|string|max:20',
                'Notes' => 'nullable|string|max:500',
                'Status' => 'nullable|integer|in:0,1',
            ]);
            
            // CALL MODEL FUNCTION (Static)
            $result = Customer::save_transaction($validated);
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Result: ' . $result);
                
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
        try {
            $customer = Customer::findOrFail($id);
            
            $validated = $request->validate([
                'Title' => 'nullable|string|max:50',
                'FirstName' => 'nullable|string|max:100',
                'LastName' => 'nullable|string|max:100',
                'BusinessName' => 'nullable|string|max:200',
                'DisplayName' => 'nullable|string|max:200',
                'Email' => 'nullable|email|max:500',
                'Mobile' => 'nullable|string|max:100',
                'Phone' => 'nullable|string|max:100',
                'Website' => 'nullable|url|max:200',
                'Address' => 'nullable|string|max:500',
                'City' => 'nullable|string|max:100',
                'Country' => 'nullable|string|max:100',
                'County' => 'nullable|string|max:100',
                'PostalCode' => 'nullable|string|max:20',
                'BillingAddress' => 'nullable|string|max:500',
                'BillingCity' => 'nullable|string|max:100',
                'BillingCountry' => 'nullable|string|max:100',
                'BillingCounty' => 'nullable|string|max:100',
                'BillingPostalCode' => 'nullable|string|max:20',
                'SameAsBilling' => 'nullable|boolean',
                'NTN' => 'nullable|string|max:20',
                'SalesTaxNumber' => 'nullable|string|max:50',
                'IsFiler' => 'nullable|boolean',
                'CNIC' => 'nullable|string|max:50',
                'CreditLimit' => 'nullable|numeric|min:0',
                'PaymentTerms' => 'nullable|string|max:20',
                'Notes' => 'nullable|string|max:500',
                'Status' => 'nullable|integer|in:0,1',
            ]);
            
            // CALL MODEL FUNCTION (Instance)
            $result = $customer->edit_transaction();
            
            return redirect()
                ->route('customers.index')
                ->with('success', 'Result: ' . $result);
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error: ' . $e->getMessage())
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
