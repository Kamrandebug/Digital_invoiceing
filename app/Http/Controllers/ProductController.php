<?php

namespace App\Http\Controllers;

use App\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        // Filters
        $filters = [
            'search'     => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        // Validation
        $rules    = [];
        $messages = [];

        // Date rules
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $rules['start_date'] = 'date';
            $rules['end_date']   = 'date|after_or_equal:start_date';
            $messages['end_date.after_or_equal'] = 'End date must be after or equal to start date.';
        }

        // Search validation
        if ($request->filled('search')) {
            $rules['search']            = 'string|max:100|regex:/^[a-zA-Z0-9\s\-\.\,\@]+$/';
            $messages['search.regex']   = 'Search term contains invalid characters.';
            $messages['search.max']     = 'Search term is too long (max 100 chars).';
        }

        if (!empty($rules)) {
            $request->validate($rules, $messages);
        }

        // Get filtered data
        $products = ProductModel::getlistingdata($filters);

        return view('pages.dashboards.products', compact('products'));
    }

    /**
     * Create form
     */
    public function create()
    {
        return view('pages.dashboards.products-form', ['mode' => 'create', 'product' => new ProductModel()]);
    }

    /**
     * Store product
     */
    public function save_transaction(Request $request)
    {
        // Format inputs
        $request->merge([
            'Name' => trim($request->Name ?? ''),
            'Code' => strtoupper(trim($request->Code ?? '')),
        ]);

        // Validate
        $validated = $request->validate([
            // Basic Info
            'Name'            => ['required', 'string', 'min:2', 'max:200'],
            'Code'            => ['required', 'string', 'max:50', 'unique:crm_product,Code'],
            'Type'            => ['nullable', 'string', 'max:50'],
            'Category'        => ['required', 'integer'],
            'SaleInformation' => ['nullable', 'string', 'max:500'],
            'Image'           => ['nullable', 'string'],

            // Pricing
            'SalePrice'       => ['required', 'numeric', 'min:0'],
            'Cost'            => ['nullable', 'numeric', 'min:0'],
            'Discount'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'PurchaseDiscount'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'TPRate'          => ['nullable', 'numeric', 'min:0'],
            'TPMargin'        => ['nullable', 'numeric', 'min:0'],
            'TrialRate'       => ['nullable', 'numeric', 'min:0'],
            'Weight'          => ['nullable', 'numeric', 'min:0'],

            // Tax
            'TaxCode'         => ['nullable', 'string', 'max:20'],
            'TaxRate'         => ['required', 'integer'],
            'NFTaxRate'       => ['nullable', 'integer'],
            'TaxSchID'        => ['nullable', 'integer'],

            // Stock
            'LowStockLevel'   => ['required', 'numeric', 'min:0'],
            'CurrentQuantity' => ['nullable', 'numeric', 'min:0'],
            'OpeningQuantity' => ['nullable', 'numeric', 'min:0'],
            'OpeningRate'     => ['nullable', 'numeric', 'min:0'],
            'Unit'            => ['nullable', 'integer'],

            // Accounts
            'IncomeAccount'   => ['required', 'integer'],
            'StockAccount'    => ['required', 'integer'],
            'ExpenseAccount'  => ['required', 'integer'],

            // Flags
            'IsSelltoCustomer'=> ['nullable', 'boolean'],
            'IsShipping'      => ['nullable', 'boolean'],
            'IsPurchasing'    => ['nullable', 'boolean'],
            'IsRawMaterial'   => ['nullable', 'boolean'],
            'purchaseStatus'  => ['nullable', 'boolean'],

            // Optional
            'OptionalField1'  => ['nullable', 'string', 'max:100'],
            'OptionalField2'  => ['nullable', 'string', 'max:100'],
            'OptionalField3'  => ['nullable', 'integer'],
            'PreferredSupplier' => ['nullable', 'integer'],

            'Status'          => ['required', 'integer', 'in:0,1'],
        ], [
            'Name.required'          => 'Product name is required.',
            'Name.min'               => 'Product name must be at least 2 characters.',
            'Code.required'          => 'Product code is required.',
            'Code.unique'            => 'This product code is already registered.',
            'Category.required'      => 'Category is required.',
            'SalePrice.required'     => 'Sale price is required.',
            'SalePrice.numeric'      => 'Sale price must be a valid number.',
            'SalePrice.min'          => 'Sale price cannot be negative.',
            'Cost.numeric'           => 'Cost must be a valid number.',
            'TaxRate.required'       => 'Tax rate is required.',
            'LowStockLevel.required' => 'Low stock level is required.',
            'IncomeAccount.required' => 'Income account is required.',
            'StockAccount.required'  => 'Stock account is required.',
            'ExpenseAccount.required'=> 'Expense account is required.',
            'Status.required'        => 'Status is required.',
            'Status.in'              => 'Invalid status value.',
        ]);

        try {
            // Generate UUID
            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;
            $now       = now()->toDateTimeString();

            // Calculate ID
            $maxId  = DB::table('temp_product')->max('ID');
            $nextId = $maxId ? $maxId + 1 : 1;

            // Prepare data
            $data = [
                'ID'               => $nextId,
                'TID'              => $nextId,
                'VoucherID'        => 0,
                'ProductID'        => null,
                'Type'             => $request->Type,
                'Name'             => $request->Name,
                'Image'            => $request->Image ?? '',
                'Code'             => $request->Code,
                'CategoryID'       => $request->Category,
                'CategoryName'     => null,
                'ParentCategoryID' => null,
                'ParentLevel1'     => 0,
                'ParentLevel2'     => 0,
                'ParentLevel3'     => 0,
                'SaleInformation'  => $request->SaleInformation ?? '',
                'SalePrice'        => $request->SalePrice ?? 0,
                'TaxCode'          => $request->TaxCode,
                'TaxRate'          => $request->TaxRate ?? 0,
                'NFTaxRate'        => $request->NFTaxRate ?? null,
                'NFTaxCode'        => null,
                'TaxRegion'        => null,
                'IncomeAccount'    => $request->IncomeAccount ?? 0,
                'LowStockLevel'    => $request->LowStockLevel ?? 0,
                'CurrentQuantity'  => $request->CurrentQuantity ?? 0,
                'Date'             => now()->toDateString(),
                'StockAccount'     => $request->StockAccount ?? 0,
                'ExpenseAccount'   => $request->ExpenseAccount ?? 0,
                'IsSelltoCustomer' => $request->input('IsSelltoCustomer', 0),
                'Status'           => $request->Status,
                'StockStatus'      => 1, // Default: Stock In
                'Cost'             => $request->Cost ?? 0,
                'OpeningQuantity'  => $request->OpeningQuantity ?? 0,
                'purchaseStatus'   => $request->input('purchaseStatus', 0),
                'OpeningRate'      => $request->OpeningRate ?? 0,
                'OptionalField1'   => $request->OptionalField1 ?? null,
                'OptionalField2'   => $request->OptionalField2 ?? null,
                'OptionalField3'   => $request->OptionalField3 ?? 0,
                'IsShipping'       => $request->input('IsShipping', 0),
                'IsPurchasing'     => $request->input('IsPurchasing', 0),
                'TPRate'           => $request->TPRate ?? 0,
                'TPMargin'         => $request->TPMargin ?? 0,
                'Unit'             => $request->Unit ?? 0,
                'UnitName'         => null,
                'UMUnitID'         => 0,
                'UMUnitName'       => null,
                'FactorRate'       => 0,
                'TrialRate'        => $request->TrialRate ?? 0,
                'Discount'         => $request->Discount ?? 0,
                'Weight'           => $request->Weight ?? 0,
                'PurchaseDiscount' => $request->PurchaseDiscount ?? 0,
                'TOSale'           => 0,
                'TOPurchase'       => 0,
                'RMSale'           => 0,
                'RMPurchase'       => 0,
                'TPSale'           => 0,
                'TPPurchase'       => 0,
                'PreferredSupplierAccount' => null,
                'PreferredSupplier'=> $request->PreferredSupplier ?? 0,
                'TaxSchID'         => $request->TaxSchID ?? 4,
                'IsRawMaterial'    => $request->input('IsRawMaterial', 0),
                'BatchNo'          => '0',
                'BatchExpiryDate'  => null,
                'ToLocationID'     => null,
                'Location'         => null,
                'ErrMsg'           => null,
                'ExcelRow'         => null,
                'Flag'             => 0,
                'CreatedBy'        => (int)$userId,
                'CreatedOn'        => $now,
                'UpdatedBy'        => (int)$userId,
                'UpdatedOn'        => $now,
                'RequestID'        => $requestId,
            ];

            // Insert into temp
            \Log::info('Inserting Product Data', ['data' => $data]);
            DB::table('temp_product')->insert($data);

            // Execute model transaction
            $query = ProductModel::save_transaction(['RequestID' => $requestId]);

            // Execute query
            if (!empty($query) && $query !== "Empty") {
                DB::statement($query);
            }

            return redirect()
                ->route('products.index')
                ->with('success', 'Product created successfully with Request ID: ' . $requestId);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show product
     */
    public function show($id)
    {
        // Read from crm_product
        $product = DB::table('crm_product')->where('ID', $id)->first();

        if (!$product) {
            abort(404, 'Product not found in CRM');
        }

        // Hydrate model
        $productModel = new ProductModel();
        $productModel->setRawAttributes((array)$product, true);
        $productModel->exists = true;

        return view('pages.dashboards.products-form', ['mode' => 'show', 'product' => $productModel]);
    }

    /**
     * Edit form
     */
    public function edit($id)
    {
        // Read from crm_product
        $product = DB::table('crm_product')->where('ID', $id)->first();

        if (!$product) {
            abort(404, 'Product not found in CRM');
        }

        // Hydrate model
        $productModel = new ProductModel();
        $productModel->setRawAttributes((array)$product, true);
        $productModel->exists = true;

        return view('pages.dashboards.products-form', ['mode' => 'edit', 'product' => $productModel]);
    }

    /**
     * Update product
     */
    public function edit_transaction(Request $request, $id)
    {
        // Get existing
        $product = DB::table('crm_product')->where('ID', $id)->first();

        if (!$product) {
            return redirect()->back()->with('error', 'Product not found.');
        }

        // Format inputs
        $request->merge([
            'Name' => trim($request->Name ?? ''),
            'Code' => strtoupper(trim($request->Code ?? '')),
        ]);

        // Validate
        $validated = $request->validate([
            // Basic Info
            'Name'            => ['required', 'string', 'min:2', 'max:200'],
            'Code'            => ['required', 'string', 'max:50', Rule::unique('crm_product', 'Code')->ignore($id, 'ID')],
            'Type'            => ['nullable', 'string', 'max:50'],
            'Category'        => ['required', 'integer'],
            'SaleInformation' => ['nullable', 'string', 'max:500'],
            'Image'           => ['nullable', 'string'],

            // Pricing
            'SalePrice'       => ['required', 'numeric', 'min:0'],
            'Cost'            => ['nullable', 'numeric', 'min:0'],
            'Discount'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'PurchaseDiscount'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'TPRate'          => ['nullable', 'numeric', 'min:0'],
            'TPMargin'        => ['nullable', 'numeric', 'min:0'],
            'TrialRate'       => ['nullable', 'numeric', 'min:0'],
            'Weight'          => ['nullable', 'numeric', 'min:0'],

            // Tax
            'TaxCode'         => ['nullable', 'string', 'max:20'],
            'TaxRate'         => ['required', 'integer'],
            'NFTaxRate'       => ['nullable', 'integer'],
            'TaxSchID'        => ['nullable', 'integer'],

            // Stock
            'LowStockLevel'   => ['required', 'numeric', 'min:0'],
            'CurrentQuantity' => ['nullable', 'numeric', 'min:0'],
            'OpeningQuantity' => ['nullable', 'numeric', 'min:0'],
            'OpeningRate'     => ['nullable', 'numeric', 'min:0'],
            'Unit'            => ['nullable', 'integer'],

            // Accounts
            'IncomeAccount'   => ['required', 'integer'],
            'StockAccount'    => ['required', 'integer'],
            'ExpenseAccount'  => ['required', 'integer'],

            // Flags
            'IsSelltoCustomer'=> ['nullable', 'boolean'],
            'IsShipping'      => ['nullable', 'boolean'],
            'IsPurchasing'    => ['nullable', 'boolean'],
            'IsRawMaterial'   => ['nullable', 'boolean'],
            'purchaseStatus'  => ['nullable', 'boolean'],

            // Optional
            'OptionalField1'  => ['nullable', 'string', 'max:100'],
            'OptionalField2'  => ['nullable', 'string', 'max:100'],
            'OptionalField3'  => ['nullable', 'integer'],
            'PreferredSupplier' => ['nullable', 'integer'],

            'Status'          => ['required', 'integer', 'in:0,1'],
        ], [
            'Name.required'          => 'Product name is required.',
            'Name.min'               => 'Product name must be at least 2 characters.',
            'Code.required'          => 'Product code is required.',
            'Code.unique'            => 'This product code is already registered.',
            'Category.required'      => 'Category is required.',
            'SalePrice.required'     => 'Sale price is required.',
            'SalePrice.numeric'      => 'Sale price must be a valid number.',
            'SalePrice.min'          => 'Sale price cannot be negative.',
            'TaxRate.required'       => 'Tax rate is required.',
            'LowStockLevel.required' => 'Low stock level is required.',
            'IncomeAccount.required' => 'Income account is required.',
            'StockAccount.required'  => 'Stock account is required.',
            'ExpenseAccount.required'=> 'Expense account is required.',
            'Status.required'        => 'Status is required.',
            'Status.in'              => 'Invalid status value.',
        ]);

        try {
            // Generate UUID
            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;
            $now       = now()->toDateTimeString();

            // Prepare data
            $data = [
                'TID'              => $id, // Ensure TID matches ID on update
                'VoucherID'        => $product->VoucherID ?? 0,
                'ProductID'        => $id,
                'Type'             => $request->Type,
                'Name'             => $request->Name,
                'Image'            => $request->Image ?? $product->Image ?? '',
                'Code'             => $request->Code,
                'CategoryID'       => $request->Category,
                'SaleInformation'  => $request->SaleInformation ?? '',
                'SalePrice'        => $request->SalePrice ?? 0,
                'TaxCode'          => $request->TaxCode,
                'TaxRate'          => $request->TaxRate ?? 0,
                'NFTaxRate'        => $request->NFTaxRate ?? null,
                'IncomeAccount'    => $request->IncomeAccount ?? 0,
                'LowStockLevel'    => $request->LowStockLevel ?? 0,
                'CurrentQuantity'  => $request->CurrentQuantity ?? $product->CurrentQuantity ?? 0,
                'StockAccount'     => $request->StockAccount ?? 0,
                'ExpenseAccount'   => $request->ExpenseAccount ?? 0,
                'IsSelltoCustomer' => $request->input('IsSelltoCustomer', 0),
                'Status'           => $request->Status,
                'Cost'             => $request->Cost ?? 0,
                'OpeningQuantity'  => $request->OpeningQuantity ?? 0,
                'purchaseStatus'   => $request->input('purchaseStatus', 0),
                'OpeningRate'      => $request->OpeningRate ?? 0,
                'OptionalField1'   => $request->OptionalField1 ?? null,
                'OptionalField2'   => $request->OptionalField2 ?? null,
                'OptionalField3'   => $request->OptionalField3 ?? 0,
                'IsShipping'       => $request->input('IsShipping', 0),
                'IsPurchasing'     => $request->input('IsPurchasing', 0),
                'TPRate'           => $request->TPRate ?? 0,
                'TPMargin'         => $request->TPMargin ?? 0,
                'Unit'             => $request->Unit ?? 0,
                'TrialRate'        => $request->TrialRate ?? 0,
                'Discount'         => $request->Discount ?? 0,
                'Weight'           => $request->Weight ?? 0,
                'PurchaseDiscount' => $request->PurchaseDiscount ?? 0,
                'PreferredSupplier'=> $request->PreferredSupplier ?? 0,
                'TaxSchID'         => $request->TaxSchID ?? 4,
                'IsRawMaterial'    => $request->input('IsRawMaterial', 0),
                'UpdatedBy'        => (int)$userId,
                'UpdatedOn'        => $now,
                'RequestID'        => $requestId,
            ];

            // Update temp
            DB::table('temp_product')->where('ID', $id)->update($data);

            // Execute model transaction
            $query = ProductModel::edit_transaction(['RequestID' => $requestId]);

            // Execute query
            if (!empty($query) && $query !== "Empty") {
                DB::statement($query);
            }

            return redirect()
                ->route('products.index')
                ->with('success', 'Product updated successfully with Request ID: ' . $requestId);

        } catch (\Exception $e) {
            \Log::error('Edit transaction failed', [
                'error'      => $e->getMessage(),
                'product_id' => $id,
                'trace'      => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error updating product: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Archive product
     */
    public function delete_transaction($id)
    {
        try {
            // Read from crm_product
            $product = DB::table('crm_product')->where('ID', $id)->first();

            if (!$product) {
                return redirect()->back()->with('error', 'Product not found.');
            }

            $requestId = (string) Str::uuid();
            $userId    = auth()->id() ?? 1;

            // Update temp
            DB::table('temp_product')
                ->where('ID', $id)
                ->update([
                    'Status'    => 2,
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => now()->toDateTimeString(),
                    'RequestID' => $requestId,
                ]);

            // Execute model transaction
            $query = ProductModel::delete_transaction(['RequestID' => $requestId]);

            if (!empty($query) && $query !== "Empty") {
                DB::statement($query);
            }

            return redirect()
                ->route('products.index')
                ->with('success', 'Product archived successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error deleting product: ' . $e->getMessage());
        }
    }

    /**
     * Export CSV
     */
    public function exportCSV(Request $request)
    {
        $filename = 'products-export-' . date('Y-m-d-His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Get filtered data
        $filters = [
            'search'     => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $products = ProductModel::getlistingdata($filters);

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');

            // Add BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($file, [
                'ID',
                'Code',
                'Name',
                'Category',
                'Sale Price',
                'Cost',
                'Current Quantity',
                'Stock Status',
                'Status',
                'Created Date'
            ]);

            foreach ($products as $product) {
                $stockStatus = match((int)$product->StockStatus) {
                    0 => 'Low Stock',
                    1 => 'In Stock',
                    2 => 'Out of Stock',
                    default => 'Unknown'
                };

                fputcsv($file, [
                    $product->ID,
                    $product->Code,
                    $product->Name,
                    $product->Category,
                    number_format($product->SalePrice, 2),
                    number_format($product->Cost, 2),
                    $product->CurrentQuantity,
                    $stockStatus,
                    $product->Status == 1 ? 'Active' : 'Inactive',
                    $product->CreatedOn,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}